<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\Settings_Store as Base_Settings;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin settings.
 */
class Settings_Store extends Base_Settings
{
    /**
     * Handle plugin settings rest controller class name.
     *
     * @var string REST Controller class name.
     */
    protected static $rest_controller_class = '\FORMS_BRIDGE\REST_Settings_Controller';

    /**
     * Inherits the parent constructor and sets up settings' validation callbacks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        $slug = Forms_Bridge::slug();

        // Patch http bridge default settings to plugin settings
        add_filter(
            'wpct_setting_default',
            static function ($default, $name) use ($slug) {
                if ($name !== $slug . '_general') {
                    return $default;
                }

                $backends = \HTTP_BRIDGE\Settings_Store::setting('general')
                    ->backends;

                return array_merge($default, ['backends' => $backends]);
            },
            10,
            2
        );

        // Patch http bridge settings to plugin settings
        add_filter(
            "option_{$slug}_general",
            static function ($value) {
                if (!is_array($value)) {
                    return $value;
                }

                $backends = \HTTP_BRIDGE\Settings_Store::setting('general')
                    ->backends;

                return array_merge($value, ['backends' => $backends]);
            },
            10,
            1
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
                    'notification_receiver' => ['type' => 'string'],
                ],
                [
                    'notification_receiver' => get_option('admin_email'),
                ],
            ],
        ];
    }

    /**
     * Validates setting data before database inserts.
     *
     * @param array $data Setting data.
     * @param Setting $setting Setting instance.
     *
     * @return array Validated setting data.
     */
    protected static function validate_setting($data, $setting)
    {
        if ($setting->name() !== 'general') {
            return $data;
        }

        $data['notification_receiver'] =
            filter_var($data['notification_receiver'], FILTER_VALIDATE_EMAIL) ?:
            get_option('admin_email');

        $http = \HTTP_BRIDGE\Settings_Store::setting('general');
        $http->backends = \HTTP_BRIDGE\Settings_Store::validate_backends(
            $data['backends'] ?? []
        );

        unset($data['backends']);
        unset($data['templates']);

        return $data;
    }
}
