<?php

namespace FORMS_BRIDGE;

use WPCT_PLUGIN\Settings_Store as Base_Settings_Store;

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

        self::ready(static function ($store) {
            $store::use_getter('general', static function ($data) {
                $backends =
                    \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?:
                    [];
                $data['backends'] = $backends;
                return $data;
            });

            $store::use_setter(
                'general',
                static function ($data) {
                    if (
                        isset($data['backends']) &&
                        is_array($data['backends'])
                    ) {
                        \HTTP_BRIDGE\Settings_Store::setting(
                            'general'
                        )->backends = $data['backends'] ?? [];
                        unset($data['backends']);
                    }

                    return $data;
                },
                9
            );
        });
    }
}
