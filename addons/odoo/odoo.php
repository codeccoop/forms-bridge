<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-odoo-db.php';
require_once 'class-odoo-form-bridge.php';
require_once 'class-odoo-form-bridge-template.php';

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
    protected static $name = 'Odoo JSON-RPC';

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
     * Handle is waiting for request response state.
     *
     * @var boolean $submitting True if is waiting for a request response, else false.
     */
    private static $submitting = false;

    /**
     * RPC payload decorator.
     *
     * @param int $session_id RPC session ID.
     * @param string $service RPC service name.
     * @param string $method RPC method name.
     * @param array $args RPC request arguments.
     *
     * @return array JSON-RPC conformant payload.
     */
    public static function rpc_payload($session_id, $service, $method, $args)
    {
        return [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'id' => $session_id,
            'params' => [
                'service' => $service,
                'method' => $method,
                'args' => $args,
            ],
        ];
    }

    /**
     * Handle RPC responses and catch errors on the application layer.
     *
     * @param array $response Request response.
     * @param boolean $is_single Should the result be an entity or an array.
     *
     * @return mixed|WP_Error Request result.
     */
    public static function rpc_response($res)
    {
        if (is_wp_error($res)) {
            return $res;
        }

        if (isset($res['data']['error'])) {
            return new WP_Error(
                $res['data']['error']['code'],
                $res['data']['error']['message'],
                $res['data']['error']['data']
            );
        }

        $data = $res['data'];

        if (empty($data['result'])) {
            return new WP_Error(
                'rpc_api_error',
                'An unkown error has ocurred with the RPC API',
                ['response' => $res]
            );
        }

        return $data['result'];
    }

    /**
     * JSON RPC login request.
     *
     * @param Odoo_DB $db Current db instance.
     * @param string $ednpoint JSON-RPC API endpoint.
     *
     * @return array|WP_Error Tuple with RPC session id and user id.
     */
    private static function rpc_login($db, $endpoint)
    {
        $session_id = Forms_Bridge::slug() . '-' . time();
        $backend = $db->backend;

        $payload = self::rpc_payload($session_id, 'common', 'login', [
            $db->name,
            $db->user,
            $db->password,
        ]);

        do_action('forms_bridge_before_odoo_rpc_login', $payload, $db);

        $response = $backend->post($endpoint, $payload);

        do_action(
            'forms_bridge_after_odoo_rpc_login',
            $response,
            $db->name,
            $db
        );

        $user_id = self::rpc_response($response);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        return [$session_id, $user_id];
    }

    /**
     * Addon constructor. Inherits from the abstract addon and initialize interceptos
     * and custom hooks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        self::interceptors();
        self::custom_hooks();
    }

    /**
     * Addon interceptors
     */
    private static function interceptors()
    {
        // Submission payload interceptor
        add_filter(
            'forms_bridge_payload',
            static function ($payload, $bridge) {
                return self::payload_interceptor($payload, $bridge);
            },
            90,
            2
        );

        // Submission response interceptor
        add_filter(
            'http_bridge_response',
            static function ($res) {
                return self::response_interceptor($res);
            },
            9
        );
    }

    /**
     * Addon custom hooks.
     */
    private static function custom_hooks()
    {
        add_filter('forms_bridge_odoo_dbs', static function ($dbs) {
            if (!wp_is_numeric_array($dbs)) {
                $dbs = [];
            }

            return array_merge($dbs, self::databases());
        });

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
        return array_map(static function ($db_data) {
            return new Odoo_DB($db_data);
        }, self::setting()->databases);
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
            [
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
                            'name' => ['type' => 'string'],
                            'database' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'model' => ['type' => 'string'],
                            'pipes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'properties' => [
                                        'from' => ['type' => 'string'],
                                        'to' => ['type' => 'string'],
                                        'cast' => [
                                            'type' => 'string',
                                            'enum' => [
                                                'boolean',
                                                'string',
                                                'integer',
                                                'float',
                                                'json',
                                                'null',
                                            ],
                                        ],
                                    ],
                                    'required' => ['from', 'to', 'cast'],
                                ],
                            ],
                        ],
                        'required' => [
                            'name',
                            'database',
                            'form_id',
                            'model',
                            'pipes',
                        ],
                    ],
                ],
            ],
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
        $data['databases'] = self::validate_databases($data['databases']);
        $data['bridges'] = self::validate_bridges(
            $data['bridges'],
            $data['databases']
        );

        return $data;
    }

    /**
     * Database setting field validation.
     *
     * @param array $dbs Databases data.
     *
     * @return array Validated databases data.
     */
    private static function validate_databases($dbs)
    {
        if (!wp_is_numeric_array($dbs)) {
            return [];
        }

        $backends = array_map(
            function ($backend) {
                return $backend['name'];
            },
            \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?: []
        );

        return array_filter($dbs, function ($db_data) use ($backends) {
            return in_array($db_data['backend'] ?? null, $backends);
        });
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with the
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     * @param array $dbs Array with databases data.
     *
     * @return array Array with valid bridge configurations.
     */
    private static function validate_bridges($bridges, $dbs)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['_id']]);
            },
            []
        );

        $templates = array_map(function ($template) {
            return $template['name'];
        }, apply_filters('forms_bridge_templates', [], 'odoo'));

        $valid_bridges = [];
        for ($i = 0; $i < count($bridges); $i++) {
            $bridge = $bridges[$i];

            // Valid only if database and form id exists
            $is_valid =
                array_reduce(
                    $dbs,
                    static function ($is_valid, $db) use ($bridge) {
                        return $bridge['database'] === $db['name'] || $is_valid;
                    },
                    false
                ) &&
                in_array($bridge['form_id'], $_ids) &&
                (empty($bridge['template']) ||
                    empty($templates) ||
                    in_array($bridge['template'], $templates));

            if ($is_valid) {
                $valid_bridges[] = $bridge;
            }
        }

        return $valid_bridges;
    }

    /**
     * Intercepts submission payloads and decorates them as RPC calls.
     *
     * @param array $payload Submission payload.
     * @param Form_Bridge $bridge Current bridge instance.
     *
     * @return array Decorated payload.
     */
    private static function payload_interceptor($payload, $bridge)
    {
        if (empty($payload)) {
            return $payload;
        }

        if ($bridge->api !== 'odoo') {
            return $payload;
        }

        $db = $bridge->database;
        $endpoint = $bridge->endpoint;
        $login = self::rpc_login($db, $endpoint);
        if ($error = is_wp_error($login) ? $login : null) {
            do_action('forms_bridge_on_failure', $bridge, $error, $payload, []);
            return;
        }

        [$sid, $uid] = $login;

        self::$submitting = true;
        return self::rpc_payload($sid, 'object', 'execute', [
            $bridge->database->name,
            $uid,
            $bridge->database->password,
            $bridge->model,
            'create',
            $payload,
        ]);
    }

    /**
     * Intercepts responses after a RPC request and checks for RPC errors on the response.
     *
     * @param array $response HTTP response.
     *
     * @return array|WP_Error Response result.
     */
    private static function response_interceptor($response)
    {
        if (!self::$submitting) {
            return $response;
        }

        self::$submitting = false;
        return self::rpc_response($response);
    }
}

Odoo_Addon::setup();
