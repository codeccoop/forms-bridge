<?php

namespace FORMS_BRIDGE;

use Error;
use Exception;
use WP_Error;
use WP_REST_Server;
use WPCT_ABSTRACT\REST_Settings_Controller as Base_Controller;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin REST API controller. Handles routes registration, permissions
 * and request callbacks.
 */
class REST_Settings_Controller extends Base_Controller
{
    /**
     * Inherits the parent initialized and register the post types route
     *
     * @param string $group Plugin settings group name.
     */
    protected static function init()
    {
        parent::init();
        self::register_forms_route();
        self::register_templates_route();
    }

    /**
     * Registers form API routes.
     */
    private static function register_forms_route()
    {
        // forms endpoint registration
        $namespace = self::namespace();
        $version = self::version();
        register_rest_route("{$namespace}/v{$version}", '/forms', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => static function () {
                return self::forms();
            },
            'permission_callback' => static function () {
                return self::permission_callback();
            },
        ]);
    }

    /**
     * Registers templates API routes.
     */
    private static function register_templates_route()
    {
        $namespace = self::namespace();
        $version = self::version();
        register_rest_route(
            "{$namespace}/v{$version}",
            '/templates/(?P<name>[a-zA-Z0-9-]+)',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => static function ($request) {
                        return self::get_template($request);
                    },
                    'permission_callback' => static function () {
                        return self::permission_callback();
                    },
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) {
                        return self::post_template($request);
                    },
                    'permission_callback' => static function () {
                        return self::permission_callback();
                    },
                    'args' => [
                        'name' => [
                            'description' => __(
                                'Name of the template to use',
                                'forms-bridge'
                            ),
                            'type' => 'string',
                            'required' => true,
                        ],
                        'integration' => [
                            'description' => __(
                                'Target integration',
                                'forms-bridge'
                            ),
                            'type' => 'string',
                            'required' => true,
                        ],
                        'fields' => [
                            'description' => __(
                                'Template fields with user inputs',
                                'forms-bridge'
                            ),
                            'type' => 'array',
                            'required' => true,
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'ref' => [
                                        'description' => __(
                                            'Field ref that points to some template param',
                                            'forms-bridge'
                                        ),
                                        'type' => 'string',
                                        'required' => true,
                                    ],
                                    'name' => [
                                        'description' => __(
                                            'Name of the field',
                                            'forms-bridge'
                                        ),
                                        'type' => 'string',
                                        'required' => true,
                                    ],
                                    'value' => [
                                        'description' => __(
                                            'Field value',
                                            'forms-bridge'
                                        ),
                                        'type' => 'mixed',
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Callback for GET requests to the forms endpoint.
     *
     * @return array Collection of array forms data.
     */
    private static function forms()
    {
        $forms = apply_filters('forms_bridge_forms', []);
        return array_map(static function ($form) {
            unset($form['bridges']);
            return $form;
        }, $forms);
    }

    /**
     * Callback for GET requests to the templates endpoint.
     *
     * @param REST_Request Request instance.
     *
     * @return array|WP_Error Template data.
     */
    private static function get_template($request)
    {
        $template_name = $request['name'];
        $template = Form_Bridge::get_template($template_name);
        if (!$template) {
            return new WP_Error(
                'not_found',
                __('Template not found', 'forms-bridge'),
                ['name' => $template_name]
            );
        }

        return $template->to_json();
    }

    /**
     * Callback for POST requests to the templates endpoint.
     *
     * @param REST_Request Request instance.
     *
     * @return array|WP_Error Template use result.
     */
    private static function post_template($request)
    {
        $name = isset($request['name'])
            ? sanitize_text_field($request['name'])
            : null;
        $fields =
            isset($request['fields']) && is_array($request['fields'])
                ? $request['fields']
                : null;
        $integration = isset($request['integration'])
            ? sanitize_text_field($request['integration'])
            : null;

        if (!($name && $fields && $integration)) {
            return new WP_Error(
                'bad_request',
                __('Invalid use template params', 'forms-bridge')
            );
        }

        if (!in_array($integration, array_keys(Integration::integrations()))) {
            return new WP_Error(
                'bad_request',
                __('Invalid use template integration', 'forms-bridge'),
                ['integration' => $integration]
            );
        }

        try {
            do_action('forms_bridge_use_template', [
                'name' => $name,
                'fields' => $fields,
                'integration' => $integration,
            ]);

            return ['success' => true];
        } catch (Form_Bridge_Template_Exception $e) {
            // Use custom exception to catch custom error status
            return new WP_Error($e->getStringCode(), $e->getMessage());
        } catch (Error | Exception $e) {
            return new WP_Error('internal_server_error', $e->getMessage());
        }
    }
}
