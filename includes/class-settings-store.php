<?php

namespace FORMS_BRIDGE;

use WPCT_PLUGIN\Settings_Store as Base_Settings_Store;
use HTTP_BRIDGE\Settings_Store as Http_Store;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin settings.
 */
class Settings_Store extends Base_Settings_Store
{
    /**
     * Handle plugin settings rest controller class name.
     *
     * @var string REST Controller class name.
     */
    protected const rest_controller_class = '\FORMS_BRIDGE\REST_Settings_Controller';

    /**
     * Inherits the parent constructor and sets up settings' validation callbacks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        $admin_email = get_option('admin_email');

        self::register_setting([
            'name' => 'general',
            'properties' => [
                'notification_receiver' => [
                    'type' => 'string',
                    'format' => 'email',
                    'default' => $admin_email,
                ],
            ],
            'required' => ['notification_receiver'],
            'default' => [
                'notification_receiver' => $admin_email,
            ],
        ]);

        self::register_setting([
            'name' => 'http',
            'properties' => [],
            'default' => [],
        ]);

        self::ready(static function ($store) {
            $store::use_getter('http', static function () {
                $setting = Http_Store::setting('general');
                return $setting->data();
            });

            $store::use_setter(
                'http',
                static function ($data) {
                    if (
                        !isset($data['backends']) ||
                        !isset($data['credentials'])
                    ) {
                        return $data;
                    }

                    $setting = Http_Store::setting('general');
                    $setting->update($data);

                    return [];
                },
                9
            );

            $store::use_cleaner('general', static function () {
                $setting = Http_Store::setting('general');
                $setting->update(['backends' => [], 'credentials' => []]);
            });
        });
    }
}
