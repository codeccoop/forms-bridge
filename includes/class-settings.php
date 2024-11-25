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
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'base_url' => ['type' => 'string'],
                            'headers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
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
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'model' => ['type' => 'string'],
                            'pipes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
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
    protected function sanitize_setting($option, $value)
    {
        [$group, $setting] = explode('_', $option);
        switch ($setting) {
            case 'general':
                $value = $this->validate_general($value);
                break;
            case 'rest-api':
            case 'rpc-api':
                $value = $this->validate_api($value);
                break;
        }

        return parent::sanitize_setting($option, $value);
    }

    /**
     * General setting validation. Remove inconsistencies with general and API settings.
     * 
     * @param array $setting General setting data.
     * 
     * @return array $setting General setting data.
     */
    private function validate_general($setting)
    {
        $rest = self::get_setting($this->get_group_name(), 'rest-api');
        $rpc = self::get_setting($this->get_group_name(), 'rpc-api');

        $hooks = $this->validate_form_hooks(
            $rest['form_hooks'],
            $setting['backends']
        );
        if (count($hooks) !== count($rest['form_hooks'])) {
            $rest['form_hooks'] = $hooks;
            update_option($this->get_group_name() . '_' . 'rest-api', $rest);
        }

        $hooks = $this->validate_form_hooks(
            $rpc['form_hooks'],
            $setting['backends']
        );
        if (count($hooks) !== count($rpc['form_hooks'])) {
            $rpc['form_hooks'] = $hooks;
            update_option($this->get_group_name() . '_' . 'rpc-api', $rpc);
        }

        return $setting;
    }

    /**
     * API settings validation. Filters API hooks with with inconsistencies with the general settings state.
     * 
     * @param array $setting Setting data.
     * 
     * @return array Validated setting data.
     */
    private function validate_api($setting)
    {
        $backends = Settings::get_setting(
            $this->get_group_name(),
            'general',
            'backends'
        );
        $setting['form_hooks'] = $this->validate_form_hooks(
            $setting['form_hooks'],
            $backends
        );
        return $setting;
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
            function ($form_ids, $form) {
                return array_merge($form_ids, [$form['id']]);
            },
            []
        );
        $valid_hooks = [];
        for ($i = 0; $i < count($form_hooks); $i++) {
            $hook = $form_hooks[$i];
            $is_valid =
                array_reduce(
                    $backends,
                    function ($is_valid, $backend) use ($hook) {
                        return $hook['backend'] === $backend['name'] ||
                            $is_valid;
                    },
                    false
                ) && in_array($hook['form_id'], $form_ids);
            if ($is_valid) {
                $valid_hooks[] = $hook;
            }
        }
        return $valid_hooks;
    }
}
