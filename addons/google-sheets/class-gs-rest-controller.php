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
    private const authorizing_transient = 'forms_bridge_authorizing';
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
            '/forms-bridge/gs-connect',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => function () {
                    return $this->connect();
                },
                'permission_callback' => function () {
                    return $this->permission_callback();
                },
            ]
        );

        register_rest_route(
            "{$namespace}/v{$version}",
            '/forms-bridge/gs-revoke',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => function () {
                    return $this->revoke();
                },
                'permission_callback' => function () {
                    return $this->permission_callback();
                },
            ]
        );

        register_rest_route(
            "{$namespace}/v{$version}",
            '/forms-bridge/gs-grant',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => function () {
                    return $this->grant();
                },
                'permission_callback' => function () {
                    return $this->permission_callback();
                },
            ]
        );

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

    private function connect()
    {
        set_transient(self::authorizing_transient, true, 60 * 5);
        return ['auth_url' => Google_Sheets_Service::auth_url()];
    }

    private function revoke()
    {
        Google_Sheets_Service::revoke_token();
        $setting = apply_filters(
            'forms_bridge_setting',
            null,
            'google-sheets-api'
        );
        return ['success' => !$setting->authorized];
    }

    private function grant()
    {
        $authorizing = get_transient(self::authorizing_transient);
        if (!$authorizing) {
            return ['success' => false];
        } else {
            delete_transient(self::authorizing_transient);
        }

        ['accessCode' => $access_code] = json_decode(
            file_get_contents('php://input'),
            true
        );
        $access_token = Google_Sheets_Service::fetch_token($access_code);

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        return ['success' => Google_Sheets_Service::is_authorized()];
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
