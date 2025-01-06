<?php

namespace FORMS_BRIDGE;

use WP_REST_Server;
use WPCT_ABSTRACT\REST_Settings_Controller as Base_Controller;

use function WPCT_ABSTRACT\is_list;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin REST API controller
 */
class REST_Settings_Controller extends Base_Controller
{
    /**
     * Handle REST API controller namespace.
     *
     * @var string $namespace Handle wp rest api plugin namespace.
     */
    protected static $namespace = 'wp-bridges';

    /**
     * Handle REST API controller namespace version.
     *
     * @var int $version Handle the API version.
     */
    protected static $version = 1;

    /**
     * Overwrite parent's contructor to register forms routes
     *
     * @param string $group_name Plugin settings group name.
     */
    public function construct(...$args)
    {
        parent::construct(...$args);

        add_action('rest_api_init', function () {
            $this->register_forms_route();
        });

        add_filter(
            'wpct_rest_settings',
            function ($settings, $group) {
                if ($group !== $this->group) {
                    return $settings;
                }

                if (!is_list($settings)) {
                    $settings = [];
                }

                return array_merge($settings, ['rest-api']);
            },
            10,
            2
        );
    }

    /**
     * Registers form API routes.
     */
    private function register_forms_route()
    {
        // forms endpoint registration
        $namespace = self::$namespace;
        $version = self::$version;
        $plugin_slug = Forms_Bridge::slug();
        register_rest_route(
            "{$namespace}/v{$version}",
            "/{$plugin_slug}/forms",
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
    }

    /**
     * GET requests forms endpoint callback.
     *
     * @return array Collection of array form representations.
     */
    private function forms()
    {
        $forms = apply_filters('forms_bridge_forms', []);
        return array_map(function ($form) {
            unset($form['hooks']);
            return $form;
        }, $forms);
    }
}
