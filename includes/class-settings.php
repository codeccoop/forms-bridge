<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\Settings as BaseSettings;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin settings.
 */
class Settings extends BaseSettings
{
    /**
     * Handle plugin settings rest controller class name.
     *
     * @var string $rest_controller_class Settings REST Controller class name.
     */
    protected static $rest_controller_class = '\FORMS_BRIDGE\REST_Settings_Controller';

    protected function construct(...$args)
    {
        parent::construct(...$args);

        add_filter(
            'wpct_sanitize_setting',
            function ($value, $setting) {
                return $this->sanitize_setting($value, $setting);
            },
            10,
            2
        );
    }

    /**
     * Registers plugin settings.
     */
    public function register()
    {
        $host = parse_url(get_bloginfo('url'))['host'];

        // Register general setting
        $this->register_setting(
            'general',
            [
                'notification_receiver' => [
                    'type' => 'string',
                ],
                'backends' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'base_url' => ['type' => 'string'],
                            'headers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'value' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'notification_receiver' => 'admin@' . $host,
                'backends' => [
                    [
                        'name' => 'ERP',
                        'base_url' => 'https://erp.' . $host,
                        'headers' => [
                            [
                                'name' => 'Authorization',
                                'value' => 'Bearer <erp-backend-token>',
                            ],
                        ],
                    ],
                ],
            ]
        );

        // Register REST API setting
        $this->register_setting(
            'rest-api',
            [
                'form_hooks' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'endpoint' => ['type' => 'string'],
                            'method' => [
                                'type' => 'string',
                                'enum' => ['GET', 'POST', 'PUT', 'DELETE'],
                            ],
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
                'form_hooks' => [],
            ]
        );

        // Register RPC API setting
        $this->register_setting(
            'rpc-api',
            [
                'endpoint' => ['type' => 'string'],
                'user' => ['type' => 'string'],
                'password' => ['type' => 'string'],
                'database' => ['type' => 'string'],
                'form_hooks' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
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
                'endpoint' => '/jsonrpc',
                'user' => 'admin',
                'password' => 'admin',
                'database' => 'erp',
                'form_hooks' => [],
            ]
        );
    }

    /**
     * Overwrites abstract sanitize callback and adds setting validation checks.
     *
     * @param string $option Option name.
     * @param array $value Setting data.
     *
     * @return array Sanitized and validated setting data.
     */
    protected function sanitize_setting($value, $setting)
    {
        if ($setting->group() !== $this->group()) {
            return $value;
        }

        $name = $setting->name();
        switch ($name) {
            case 'general':
                $value = $this->validate_general($value);
                break;
            case 'rest-api':
            case 'rpc-api':
                $value = $this->validate_api($value);
                break;
        }

        return $value;
    }

    /**
     * General setting validation. Remove inconsistencies with general and API settings.
     *
     * @param array $value General setting data.
     *
     * @return array $setting General setting data.
     */
    private function validate_general($value)
    {
        $value['notification_receiver'] = sanitize_text_field(
            $value['notification_receiver']
        );
        $value['backends'] = \HTTP_BRIDGE\Settings::validate_backends(
            $value['backends']
        );

        $rest = self::get_setting($this->group(), 'rest-api');
        $rpc = self::get_setting($this->group(), 'rpc-api');

        $hooks = $this->validate_form_hooks(
            $rest->form_hooks,
            $value['backends']
        );
        if (count($hooks) !== count($rest->form_hooks)) {
            $rest->form_hooks = $hooks;
        }

        $hooks = $this->validate_form_hooks(
            $rpc->form_hooks,
            $value['backends']
        );
        if (count($hooks) !== count($rpc->form_hooks)) {
            $rpc->form_hooks = $hooks;
        }

        return $value;
    }

    /**
     * API settings validation. Filters API hooks with with inconsistencies with the general settings state.
     *
     * @param array $setting Setting data.
     *
     * @return array Validated setting data.
     */
    private function validate_api($value)
    {
        $backends = Settings::get_setting($this->group(), 'general')->backends;

        $value['form_hooks'] = $this->validate_form_hooks(
            $value['form_hooks'],
            $backends
        );

        return $value;
    }

    /**
     * Validate form hooks settings. Filters form hooks with inconsistencies with the existing backends.
     *
     * @param array $form_hooks Array with form hooks configurations.
     * @param array $backends Array with HTTP_Backend instances.
     *
     * @return array Array with valid form hook configurations.
     */
    private function validate_form_hooks($form_hooks, $backends)
    {
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

            // Valid only if backend and form id exists
            $is_valid =
                array_reduce(
                    $backends,
                    static function ($is_valid, $backend) use ($hook) {
                        return $hook['backend'] === $backend['name'] ||
                            $is_valid;
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
                $hook['backend'] = sanitize_text_field($hook['backend']);
                $hook['form_id'] = (int) $hook['form_id'];

                if (isset($hook['model'])) {
                    $hook['model'] = sanitize_text_field($hook['model']);
                } else {
                    if (
                        !in_array($hook['method'], [
                            'GET',
                            'POST',
                            'PUT',
                            'DELETE',
                        ])
                    ) {
                        $hook['method'] = null;
                    }
                    $hook['endpoint'] = sanitize_text_field($hook['endpoint']);
                }

                $valid_hooks[] = $hook;
            }
        }
        return $valid_hooks;
    }
}
