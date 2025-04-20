<?php

namespace FORMS_BRIDGE;

use WP_REST_Server;
use HTTP_BRIDGE\Http_Backend;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-odoo-db.php';
require_once 'class-odoo-form-bridge.php';
require_once 'class-odoo-form-bridge-template.php';

require_once 'api-functions.php';

/**
 * Odoo Addon class.
 */
class Odoo_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'Odoo';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'odoo';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Odoo_Form_Bridge';

    /**
     * Handles the addon's custom bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Odoo_Form_Bridge_Template';

    /**
     * Addon constructor. Inherits from the abstract addon and initialize interceptos
     * and custom hooks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);
        self::custom_hooks();

        add_action(
            'rest_api_init',
            static function () {
                $namespace = REST_Settings_Controller::namespace();
                $version = REST_Settings_Controller::version();

                register_rest_route("{$namespace}/v{$version}", '/odoo/users', [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) {
                        $params = $request->get_json_params();
                        return self::fetch_users($params);
                    },
                    'permission_callback' => static function () {
                        return REST_Settings_Controller::permission_callback();
                    },
                ]);

                register_rest_route("{$namespace}/v{$version}", '/odoo/tags', [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) {
                        $params = $request->get_json_params();
                        return self::fetch_tags($params);
                    },
                    'permission_callback' => static function () {
                        return REST_Settings_Controller::permission_callback();
                    },
                ]);

                register_rest_route("{$namespace}/v{$version}", '/odoo/teams', [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) {
                        $params = $request->get_json_params();
                        return self::fetch_teams($params);
                    },
                    'permission_callback' => static function () {
                        return REST_Settings_Controller::permission_callback();
                    },
                ]);

                register_rest_route("{$namespace}/v{$version}", '/odoo/lists', [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) {
                        $params = $request->get_json_params();
                        return self::fetch_lists($params);
                    },
                    'permission_callback' => static function () {
                        return REST_Settings_Controller::permission_callback();
                    },
                ]);
            },
            10,
            0
        );
    }

    /**
     * Addon custom hooks.
     */
    private static function custom_hooks()
    {
        add_filter(
            'forms_bridge_odoo_dbs',
            static function ($dbs) {
                if (!wp_is_numeric_array($dbs)) {
                    $dbs = [];
                }

                return array_merge($dbs, self::databases());
            },
            10,
            1
        );

        add_filter(
            'forms_bridge_odoo_db',
            static function ($db, $name) {
                if ($db instanceof Odoo_DB) {
                    return $db;
                }

                $dbs = self::databases();
                foreach ($dbs as $db) {
                    if ($db->name === $name) {
                        return $db;
                    }
                }
            },
            10,
            2
        );
    }

    /**
     * Addon databases instances getter.
     *
     * @return array List with available databases instances.
     */
    private static function databases()
    {
        return array_map(
            static function ($data) {
                return new Odoo_DB($data);
            },
            self::setting()->databases ?: []
        );
    }

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            self::$api,
            self::merge_setting_config([
                'databases' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'user' => ['type' => 'string'],
                            'password' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                        ],
                        'required' => ['name', 'user', 'password', 'backend'],
                    ],
                ],
                'bridges' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'database' => ['type' => 'string'],
                            'model' => ['type' => 'string'],
                        ],
                        'required' => ['database', 'model'],
                    ],
                ],
            ]),
            [
                'databases' => [],
                'bridges' => [],
            ],
        ];
    }

    /**
     * Apply settings' data validations before db updates.
     *
     * @param array $data Setting data.
     * @param Setting $setting Setting instance.
     *
     * @return array Validated setting data.
     */
    protected static function validate_setting($data, $setting)
    {
        $backends =
            \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?: [];
        $data['databases'] = self::validate_databases(
            $data['databases'],
            $backends
        );
        $data['bridges'] = self::validate_bridges(
            $data['bridges'],
            $data['databases']
        );

        return $data;
    }

    /**
     * Database setting field validation. Filters inconsistent databases
     * based on the Http_Bridge's backends store state.
     *
     * @param array $databases Databases data.
     * @param array $backends Backends data.
     *
     * @return array Validated databases data.
     */
    private static function validate_databases($databases, $backends)
    {
        if (!wp_is_numeric_array($databases)) {
            return [];
        }

        $backends = array_map(function ($backend) {
            return $backend['name'];
        }, $backends);

        $uniques = [];
        $validated = [];
        foreach ($databases as $database) {
            if (empty($database['name'])) {
                continue;
            }

            if (in_array($database['name'], $uniques)) {
                continue;
            } else {
                $uniques[] = $database['name'];
            }

            if (!in_array($database['backend'] ?? null, $backends)) {
                $database['backend'] = '';
            }

            $database['user'] = $database['user'] ?? '';
            $database['password'] = $database['password'] ?? '';

            $validated[] = $database;
        }

        return $validated;
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with the
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     * @param array $databases Array with databases data.
     *
     * @return array Validated bridge configurations.
     */
    private static function validate_bridges($bridges, $databases)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $db_names = array_map(function ($database) {
            return $database['name'];
        }, $databases);

        $uniques = [];
        $validated = [];
        foreach ($bridges as $bridge) {
            $bridge = self::validate_bridge($bridge, $uniques);

            if (!$bridge) {
                continue;
            }

            if (!in_array($bridge['database'], $db_names)) {
                $bridge['database'] = '';
            }

            $bridge['model'] = $bridge['model'] ?? '';

            $bridge['is_valid'] =
                $bridge['is_valid'] &&
                !empty($bridge['database']) &&
                !empty($bridge['model']);

            $validated[] = $bridge;
        }

        return $validated;
    }

    private static function rpc_fetch($model, $params, $fields = [])
    {
        $database_params = $params['database'];
        $backend_params = $params['backend'];
        $database_params['backend'] = $backend_params['name'];

        if (!$database_params['backend']) {
            return new WP_Error(
                'bad_request',
                __('Invalid database params', 'forms-bridge'),
                ['backend' => $database_params, 'database' => $database_params]
            );
        }

        add_filter(
            'forms_bridge_odoo_db',
            static function ($database, $name) use ($database_params) {
                if ($database instanceof Odoo_DB) {
                    return $database;
                }

                if ($name === $database_params['name']) {
                    return new Odoo_DB($database_params);
                }
            },
            20,
            2
        );

        add_filter(
            'http_bridge_backend',
            static function ($backend, $name) use ($backend_params) {
                if ($backend instanceof Http_Backend) {
                    return $backend;
                }

                if ($name === $backend_params['name']) {
                    return new Http_Backend($backend_params);
                }
            },
            20,
            2
        );

        $bridge = new Odoo_Form_Bridge([
            'name' => "odoo-rpc-{$model}-fetch",
            'database' => $database_params['name'],
            'model' => $model,
            'method' => 'search_read',
        ]);

        $response = $bridge->submit([], $fields);

        if (is_wp_error($response)) {
            return $response;
        }

        return $response['data']['result'];
    }

    private static function fetch_users($params)
    {
        $users = self::rpc_fetch('res.users', $params, ['id', 'email']);

        if (is_wp_error($users)) {
            return [];
        }

        return $users;
    }

    private static function fetch_tags($params)
    {
        $tags = self::rpc_fetch('crm.tag', $params, ['id', 'name']);

        if (is_wp_error($tags)) {
            return [];
        }

        return $tags;
    }

    private static function fetch_teams($params)
    {
        $tags = self::rpc_fetch('crm.team', $params, ['id', 'name']);

        if (is_wp_error($tags)) {
            return [];
        }

        return $tags;
    }

    private static function fetch_lists($params)
    {
        $tags = self::rpc_fetch('mailing.list', $params, ['id', 'name']);

        if (is_wp_error($tags)) {
            return [];
        }

        return $tags;
    }

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array Ping result.
     */
    protected function do_ping($backend, $request)
    {
        $bridge = new Odoo_Form_Bridge([
            'name' => '__odoo-' . time(),
            'model' => 'res.users',
            'database' => $request['database'],
        ]);

        $response = $bridge->submit([]);
        return ['success' => is_wp_error($response)];
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array Fetched records.
     */
    protected function do_fetch($backend, $endpoint, $request)
    {
        $database = $request['credentials'];

        $bridge = new Odoo_Form_Bridge([
            'name' => '__odoo-' . time(),
            'method' => 'search_read',
            'model' => $endpoint,
            'backend' => $backend,
            'database' => $database['name'],
        ]);

        $response = $bridge->submit([]);
        if (is_wp_error($response)) {
            return [];
        }

        $data = $response['data']['data'];
        return $data['results'] ?? $data;
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields
     * and accepted content type.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array List of fields and content type of the endpoint.
     */
    protected function get_schema($backend, $endpoint, $request)
    {
        $bridge = new Odoo_Form_Bridge([
            'name' => '__odoo-' . time(),
            'model' => $endpoint,
            'backend' => $backend,
            'database' => $request['database'],
        ]);

        return $bridge->api_fields;
    }
}

Odoo_Addon::setup();
