<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\REST_Settings_Controller as Base_Controller;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin REST API controller
 */
class REST_Settings_Controller extends Base_Controller
{
    /**
     * @var string $namespace Handle wp rest api plugin namespace.
     */
    protected static $namespace = 'wp-bridges';

    /**
     * @var int $version Handle the API version.
     */
    protected static $version = 1;

    /**
     * @var array $settings Handle the plugin settings names list.
     */
    protected static $settings = ['general', 'rest-api', 'rpc-api'];

    /**
     * Overwrite parent's contructor to register forms routes
     *
     * @param string $group_name Plugin settings group name.
     */
    public function __construct($group_name)
    {
        parent::__construct($group_name);

        add_action('rest_api_init', function () {
            $this->init_forms();
        });
    }

    /**
     * Registers form API routes.
     */
    private function init_forms()
    {
        // forms endpoint registration
        $namespace = self::$namespace;
        $version = self::$version;
        register_rest_route("{$namespace}/v{$version}", '/forms-bridge/forms', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => function () {
                return $this->forms();
            },
            'permission_callback' => function () {
                return $this->permission_callback();
            },
        ]);
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
}
