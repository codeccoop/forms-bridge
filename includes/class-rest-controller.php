<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT/Singleton;
use WP_Error;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin REST API controller
 */
class REST_Controller extends Singleton
{
    /**
     * @var string $namespace Handle wp rest api plugin namespace.
     */
    private $namespace = 'wp-bridges';

    /**
     * @var int $version Handle the API version.
     */
    private $version = 1;

    /**
     * @var array $settings Handle the plugin settings names list.
     */
    private static $settings = ['general', 'rest-api', 'rpc-api'];

    /**
     * Setup a new rest api controller.
     *
     * @return object $controller Instance of REST_Controller.
     */
    public static function setup()
    {
        return new REST_Controller();
    }

    /**
     * Internal WP_Error proxy.
     *
     * @param string $code
     * @param string $message
     * @param int $status
     */
    private static function error($code, $message, $status)
    {
        return new WP_Error($code, __($message, 'forms-bridge'), [
            'status' => $status,
        ]);
    }

    /**
     * Binds class initializer to the rest_api_init hook
     */
    public function __construct()
    {
        add_action('rest_api_init', function () {
            $this->init();
        });
    }

    /**
     * REST_Controller initializer.
     */
    private function init()
    {
        // register forms endpoint
        register_rest_route(
            "{$this->namespace}/v{$this->version}",
            '/forms-bridge/forms',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => function () {
                    return $this->forms();
                },
                'permission_callback' => function () {
                    return $this->permission_callback();
                },
            ]
        );

        // register settings endpoint
        register_rest_route(
            "{$this->namespace}/v{$this->version}",
            '/forms-bridge/settings/',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => function () {
                        return $this->get_settings();
                    },
                    'permission_callback' => function () {
                        return $this->permission_callback();
                    },
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => function () {
                        return $this->set_settings();
                    },
                    'permission_callback' => function () {
                        return $this->permission_callback();
                    },
                ],
            ]
        );
    }

    /**
     * GET requests forms endpoint callback.
     *
     * @return array $forms Collection of array form representations.
     */
    private function forms()
    {
        return apply_filters('forms_bridge_forms', []);
    }

    /**
     * GET requests settings endpoint callback.
     *
     * @return array $settings Associative array with settings data.
     */
    private function get_settings()
    {
        $settings = [];
        foreach (self::$settings as $setting) {
            $settings[$setting] = Settings::get_setting(
                'forms-bridge',
                $setting
            );
        }
        return $settings;
    }

    /**
     * POST requests settings endpoint callback. Store settings on the options table.
     *
     * @return array $response New settings state.
     */
    private function set_settings()
    {
        $data = (array) json_decode(file_get_contents('php://input'), true);
        $response = [];
        foreach (self::$settings as $setting) {
            if (!isset($data[$setting])) {
                continue;
            }

            $from = Settings::get_setting('forms-bridge', $setting);
            $to = $data[$setting];
            foreach (array_keys($from) as $key) {
                $to[$key] = isset($to[$key]) ? $to[$key] : $from[$key];
            }
            update_option('forms-bridge_' . $setting, $to);
            $response[$setting] = $to;
        }

        return $response;
    }

    /**
     * Check if current user can manage options
     *
     * @return boolean $allowed
     */
    private function permission_callback()
    {
        return current_user_can('manage_options')
            ? true
            : self::error(
                'rest_unauthorized',
                'You can\'t manage wp options',
                403
            );
    }
}
