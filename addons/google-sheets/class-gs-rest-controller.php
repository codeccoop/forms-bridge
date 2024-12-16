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
    private static $namespace = 'wp-bridges';
    private static $version = 1;

    public static function setup()
    {
        return self::get_instance();
    }

    protected function construct(...$args)
    {
        add_action('rest_api_init', function () {
            $this->init();
        });
    }

    private function init()
    {
        $namespace = self::$namespace;
        $version = self::$version;

        register_rest_route(
            "{$namespace}/v{$version}",
            '/forms-bridge/spreadsheets',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => function () {
                    return $this->spreadsheets();
                },
                'permission_callback' => function () {
                    return $this->permission_callback();
                },
            ]
        );
    }

    private function spreadsheets()
    {
        return Google_Sheets_Service::get_spreadsheets();
    }

    /**
     * Check if current user can manage options.
     *
     * @return boolean $allowed
     */
    protected function permission_callback()
    {
        return current_user_can('manage_options')
            ? true
            : new WP_Error(
                'rest_unauthorized',
                __('You can\'t manage wp options', 'forms-brdige'),
                ['code' => 403]
            );
    }
}

Google_Sheet_REST_Controller::setup();
