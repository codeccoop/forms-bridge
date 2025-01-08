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
     * Class constructor. Inherits the parent constructor and setup settings validation
     * callbacks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        add_filter(
            'wpct_validate_setting',
            function ($data, $setting) {
                return $this->validate_setting($data, $setting);
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
                'notification_receiver' => get_option('admin_email'),
                'backends' => [],
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
                                                'csv',
                                                'concat',
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
    }

    /**
     * Validate setting data callback.
     *
     * @param array $data Setting data.
     * @param Setting $setting Setting instance.
     *
     * @return array Validated setting data.
     */
    protected function validate_setting($data, $setting)
    {
        if ($setting->group() !== $this->group()) {
            return $data;
        }

        $name = $setting->name();
        switch ($name) {
            case 'general':
                $value = $this->validate_general($data);
                break;
            case 'rest-api':
                $value = $this->validate_api($data);
                break;
        }

        return $value;
    }

    /**
     * General setting validation. Remove inconsistencies between general and API settings.
     *
     * @param array $data General setting data.
     *
     * @return array General setting validated data.
     */
    private function validate_general($data)
    {
        $data['backends'] = \HTTP_BRIDGE\Settings::validate_backends(
            $data['backends']
        );

        return $data;
    }

    /**
     * API settings validation. Filters inconsistent API hooks based on the general settings state.
     *
     * @param array $data Setting data.
     *
     * @return array Validated setting data.
     */
    private function validate_api($data)
    {
        $backends = Settings::get_setting($this->group(), 'general')->backends;

        $data['form_hooks'] = $this->validate_form_hooks(
            $data['form_hooks'],
            $backends
        );

        return $data;
    }

    /**
     * Validate form hooks settings. Filters form hooks with inconsistencies with the existing backends.
     *
     * @param array $form_hooks Array with form hooks configurations.
     * @param array $backends Array with backends data.
     *
     * @return array Array with valid form hook configurations.
     */
    private function validate_form_hooks($form_hooks, $backends)
    {
        if (!is_array($form_hooks)) {
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

            // Valid only if backend and form id exists
            $is_valid =
                array_reduce(
                    $backends,
                    static function ($is_valid, $backend) use ($hook) {
                        return $hook['backend'] === $backend['name'] ||
                            $is_valid;
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
