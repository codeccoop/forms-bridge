<?php

namespace FORMS_BRIDGE;

use WP_Error;

use function WPCT_ABSTRACT\is_list;

require_once 'class-odoo-db.php';
require_once 'class-odoo-form-hook.php';

class Odoo_Plugin extends Addon
{
    public static $name = 'Odoo JSON-RPC';
    public static $slug = 'odoo-api';

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
        if (is_wp_error($res) || empty($res['data'])) {
            return $res;
        }

        if (isset($res['data']['error'])) {
            return new WP_Error(
                $res['data']['error']['code'],
                $res['data']['error']['message'],
                $res['data']['error']['data']
            );
        } else {
            $res['data'] = $res['data']['result'];
        }

        return $res;
    }

    /**
     * JSON RPC login request.
     *
     * @param Form_Hook Current hook instance.
     *
     * @return array Tuple with RPC session id and user id.
     */
    private static function rpc_login($db)
    {
        $session_id = 'forms-bridge-' . time();
        $backend = $db->backend;

        $payload = self::rpc_payload($session_id, 'common', 'login', [
            $db->name,
            $db->user,
            $db->password,
        ]);

        do_action('forms_bridge_before_odoo_rpc_login', $payload, $db);
        $response = $backend->post($backend->endpoint, $payload);
        do_action(
            'forms_bridge_after_odoo_rpc_login',
            $response,
            $db->name,
            $db
        );

        $user_id = self::rpc_response($response);
        if (is_wp_error($user_id)) {
            $user_id;
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
            'http_bridge_backend_headers',
            function ($headers, $backend) {
                return $this->headers_interceptor($headers, $backend);
            },
            9,
            2
        );

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

        add_filter(
            'forms_bridge_form_hooks',
            function ($form_hooks, $form_id) {
                return $this->form_hooks_interceptor($form_hooks, $form_id);
            },
            9,
            2
        );
    }

    private function custom_hooks()
    {
        add_filter(
            'forms_bridge_odoo_dbs',
            function ($dbs) {
                if (!is_list($dbs)) {
                    $dbs = [];
                }

                return array_merge($dbs, $this->databases());
            },
            10
        );

        add_filter(
            'forms_bridge_odoo_db',
            function ($db, $name) {
                if (is_array($db)) {
                    return $db;
                }

                $dbs = $this->databases();
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

    private function setting()
    {
        return apply_filters('forms_bridge_setting', null, 'odoo-api');
    }

    private function databases()
    {
        return array_map(function ($db_data) {
            return new Odoo_DB($db_data);
        }, $this->setting()->databases);
    }

    private function form_hooks($form_id = null)
    {
        $form_hooks = array_map(function ($hook_data) {
            return new Odoo_Form_Hook($hook_data);
        }, $this->setting()->form_hooks);

        if ($form_id) {
            $form_hooks = array_values(
                array_filter($form_hooks, function ($hook) use ($form_id) {
                    return (int) $hook->form_id === (int) $form_id;
                })
            );
        }

        return $form_hooks;
    }

    protected function register_setting($settings)
    {
        $settings->register_setting(
            'odoo-api',
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

    /**
     *
     */
    private function headers_interceptor($headers, $backend)
    {
        $form_data = apply_filters('forms_bridge_form', null);
        if (empty($form_data)) {
            return $headers;
        }

        $form_hooks = $form_data['hooks'];

        // TODO: Warning!! If two or more hooks points to the same backend with different
        // api schemas and odoo is included, this will force all requests to be
        // done as json. [Edge case]
        $is_rpc = false;
        foreach ($form_hooks as $form_hook) {
            if (
                $form_hook->backend->name === $backend->name &&
                $form_hook->api === 'odoo'
            ) {
                $is_rpc = true;
                break;
            }
        }

        if (!$is_rpc) {
            return $headers;
        }

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        return $headers;
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
        $login = self::rpc_login($db);
        if (is_wp_error($login)) {
            return null;
        }

        [$sid, $uid] = $login;

        $this->submitting = true;
        return self::rpc_payload($sid, 'object', 'execute', [
            $form_hook->database->name,
            $uid,
            $form_hook->password,
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

    private function form_hooks_interceptor($form_hooks, $form_id)
    {
        if (!is_list($form_hooks)) {
            $form_hooks = [];
        }

        return array_merge($form_hooks, $this->form_hooks($form_id));
    }

    protected function sanitize_setting($value, $setting)
    {
        if ($setting->full_name() !== 'forms-bridge_odoo-api') {
            return $value;
        }

        $value['databases'] = $this->validate_databases($value['databases']);
        $value['form_hooks'] = $this->validate_form_hooks(
            $value['form_hooks'],
            $value['databases']
        );

        return $value;
    }

    private function validate_databases($dbs)
    {
        if (!is_list($dbs)) {
            return [];
        }

        return array_map(
            function ($db_data) {
                $db_data['name'] = sanitize_text_field($db_data['name']);
                $db_data['user'] = sanitize_text_field($db_data['user']);
                $db_data['password'] = sanitize_text_field(
                    $db_data['password']
                );
                $db_data['backend'] = sanitize_text_field($db_data['backend']);
                return $db_data;
            },
            array_filter($dbs, function ($db_data) {
                return isset(
                    $db_data['name'],
                    $db_data['user'],
                    $db_data['password'],
                    $db_data['backend']
                );
            })
        );
    }

    private function validate_form_hooks($form_hooks, $dbs)
    {
        if (!is_list($form_hooks)) {
            return [];
        }

        $form_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['id']]);
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
                ) && in_array($hook['form_id'], $form_ids);

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

                $hook['name'] = sanitize_text_field($hook['name']);
                $hook['form_id'] = (int) $hook['form_id'];
                $hook['model'] = sanitize_text_field($hook['model']);
                $hook['database'] = sanitize_text_field($hook['database']);

                $pipes = [];
                foreach ($hook['pipes'] as $pipe) {
                    $pipe['to'] = sanitize_text_field($pipe['to']);
                    $pipe['from'] = sanitize_text_field($pipe['from']);
                    $pipe['cast'] = in_array($pipe['cast'], [
                        'boolean',
                        'string',
                        'integer',
                        'float',
                        'json',
                        'null',
                    ])
                        ? $pipe['cast']
                        : 'string';
                    $pipes[] = $pipe;
                }
                $hook['pipes'] = $pipes;

                $valid_hooks[] = $hook;
            }
        }

        return $valid_hooks;
    }
}

Odoo_Plugin::setup();
