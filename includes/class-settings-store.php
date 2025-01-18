<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\SettingsStore as BaseSettings;

use function WPCT_ABSTRACT\is_list;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin settings.
 */
class SettingsStore extends BaseSettings
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
            static function ($data, $setting) {
                return self::validate_setting($data, $setting);
            },
            10,
            2
        );
    }

    /**
     * Plugin's setting configuration.
     */
    public static function config()
    {
        return [
            [
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
                ],
            ],
            [
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
                ],
            ],
        ];
    }

    /**
     * Validate setting data callback.
     *
     * @param array $data Setting data.
     * @param Setting $setting Setting instance.
     *
     * @return array Validated setting data.
     */
    protected static function validate_setting($data, $setting)
    {
        if ($setting->group() !== self::group()) {
            return $data;
        }

        $name = $setting->name();
        switch ($name) {
            case 'general':
                $data = self::validate_general($data);
                break;
            case 'rest-api':
                $data = self::validate_api($data);
                break;
        }

        return $data;
    }

    /**
     * General setting validation. Remove inconsistencies between general and API settings.
     *
     * @param array $data General setting data.
     *
     * @return array General setting validated data.
     */
    private static function validate_general($data)
    {
        $data['backends'] = \HTTP_BRIDGE\SettingsStore::validate_backends(
            $data['backends']
        );

        return $data;
    }

    /**
     * Apply settings' data validations before db updates.
     *
     * @param array $data Setting data.
     *
     * @return array Validated setting data.
     */
    private static function validate_api($data)
    {
        $backends = Forms_Bridge::setting('general')->backends;

        $data['form_hooks'] = self::validate_form_hooks(
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
    private static function validate_form_hooks($form_hooks, $backends)
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

        $names = array_unique(
            array_map(function ($form_hook) {
                return $form_hook['name'];
            }, $valid_hooks)
        );

        $uniques = [];
        foreach ($valid_hooks as $form_hook) {
            if (in_array($form_hook['name'], $names, true)) {
                $uniques[] = $form_hook;
                $index = array_search($form_hook['name'], $names);
                unset($names[$index]);
            }
        }

        return $uniques;
    }
}
