<?php

namespace FORMS_BRIDGE;

use WP_Error;

use function WPCT_ABSTRACT\is_list;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-odoo-db.php';
require_once 'class-odoo-form-hook.php';

class Odoo_Addon extends Addon
{
    protected static $name = 'Odoo JSON-RPC';
    protected static $slug = 'odoo-api';
    protected static $hook_class = '\FORMS_BRIDGE\Odoo_Form_Hook';

    /**
     * Handle is waiting for request response state.
     *
     * @var boolean $submitting True if is waiting for a request response, else false.
     */
    private $submitting = false;

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

    protected function construct(...$args)
    {
        parent::construct(...$args);
        $this->interceptors();
        $this->custom_hooks();
    }

    private function interceptors()
    {
        add_filter(
            'forms_bridge_payload',
            function ($payload, $uploads, $hook) {
                return $this->payload_interceptor($payload, $hook);
            },
            9,
            3
        );

        add_filter(
            'http_bridge_response',
            function ($res, $req) {
                return $this->response_interceptor($res);
            },
            9,
            2
        );
    }

    private function custom_hooks()
    {
        add_filter('forms_bridge_odoo_dbs', static function ($dbs) {
            if (!is_list($dbs)) {
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

    private static function databases()
    {
        return array_map(static function ($db_data) {
            return new Odoo_DB($db_data);
        }, self::setting()->databases);
    }

    protected static function register_setting($settings)
    {
        $settings->register_setting(
            self::$slug,
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
                    ],
                ],
                'form_hooks' => [
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
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'databases' => [],
                'form_hooks' => [],
            ]
        );
    }

    private function payload_interceptor($payload, $form_hook)
    {
        if (empty($payload)) {
            return $payload;
        }

        if ($form_hook->api !== 'odoo') {
            return $payload;
        }

        $db = $form_hook->database;
        $endpoint = $form_hook->endpoint;
        $login = self::rpc_login($db, $endpoint);
        if (is_wp_error($login)) {
            $form_data = apply_filters(
                'forms_bridge_form',
                null,
                $form_hook->integration
            );
            do_action(
                'forms_bridge_on_failure',
                $payload,
                [],
                $form_data,
                $login->get_error_data()
            );
            return;
        }

        [$sid, $uid] = $login;

        $this->submitting = true;
        return self::rpc_payload($sid, 'object', 'execute', [
            $form_hook->database->name,
            $uid,
            $form_hook->database->password,
            $form_hook->model,
            'create',
            $payload,
        ]);
    }

    private function response_interceptor($response)
    {
        if (!$this->submitting) {
            return $response;
        }

        $this->submitting = false;
        return self::rpc_response($response);
    }

    protected static function sanitize_setting($value, $setting)
    {
        $value['databases'] = self::validate_databases($value['databases']);
        $value['form_hooks'] = self::validate_form_hooks(
            $value['form_hooks'],
            $value['databases']
        );

        return $value;
    }

    private static function validate_databases($dbs)
    {
        if (!is_list($dbs)) {
            return [];
        }

        $backends = array_map(function ($backend) {
            return $backend['name'];
        }, Forms_Bridge::setting('general')->backends);

        return array_filter($dbs, function ($db_data) use ($backends) {
            return isset(
                $db_data['name'],
                $db_data['user'],
                $db_data['password'],
                $db_data['backend']
            ) && in_array($db_data['backend'], $backends);
        });
    }

    private static function validate_form_hooks($form_hooks, $dbs)
    {
        if (!is_list($form_hooks)) {
            return [];
        }

        $_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['_id']]);
            },
            []
        );

        $valid_hooks = [];
        for ($i = 0; $i < count($form_hooks); $i++) {
            $hook = $form_hooks[$i];

            // Valid only if database and form id exists
            $is_valid =
                array_reduce(
                    $dbs,
                    static function ($is_valid, $db) use ($hook) {
                        return $hook['database'] === $db['name'] || $is_valid;
                    },
                    false
                ) && in_array($hook['form_id'], $_ids);

            if ($is_valid) {
                // filter empty pipes
                $hook['pipes'] = isset($hook['pipes'])
                    ? (array) $hook['pipes']
                    : [];
                $hook['pipes'] = array_filter($hook['pipes'], static function (
                    $pipe
                ) {
                    return $pipe['to'] && $pipe['from'] && $pipe['cast'];
                });

                $valid_hooks[] = $hook;
            }
        }

        return $valid_hooks;
    }
}

Odoo_Addon::setup();
