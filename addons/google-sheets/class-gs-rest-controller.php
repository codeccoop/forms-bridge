<?php

namespace FORMS_BRIDGE;

use WP_Error;
use WP_REST_Server;
use WPCT_ABSTRACT\Singleton;

if (!defined('ABSPATH')) {
    exit();
}

class Google_Sheet_REST_Controller extends Singleton
{
    private static $namespace = 'forms-bridge';
    private static $version = 1;

    public static function setup()
    {
        return self::get_instance();
    }

    protected function construct(...$args)
    {
        add_action('rest_api_init', static function () {
            self::init();
        });
    }

    private static function init()
    {
        $namespace = self::$namespace;
        $version = self::$version;

        register_rest_route("{$namespace}/v{$version}", '/spreadsheets', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => static function () {
                return self::spreadsheets();
            },
            'permission_callback' => static function () {
                return self::permission_callback();
            },
        ]);
    }

    private static function spreadsheets()
    {
        return Google_Sheets_Service::get_spreadsheets();
    }

    /**
     * Check if current user can manage options.
     *
     * @return boolean $allowed
     */
    protected static function permission_callback()
    {
        return current_user_can('manage_options') ?:
            new WP_Error(
                'rest_unauthorized',
                __('You can\'t manage wp options', 'forms-bridge'),
                ['code' => 403]
            );
    }
}

Google_Sheet_REST_Controller::setup();
