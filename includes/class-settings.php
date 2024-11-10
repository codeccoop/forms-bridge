<?php

namespace WPCT_ERP_FORMS;

use WPCT_ABSTRACT\Settings as BaseSettings;

/**
 * Plugin settings.
 *
 * @since 1.0.0
 */
class Settings extends BaseSettings
{
    /**
     * Return registered backends.
     *
     * @return object $instance Class instance.
     *
     * @since 3.0.0
     *
     * @return array $backends Collection of backend array representations.
     */
    public static function get_backends()
    {
        $setting = Settings::get_setting('wpct-erp-forms', 'general');
        return array_map(function ($backend) {
            return $backend['name'];
        }, $setting['backends']);
    }

    /**
     * Get form instances from database.
     *
     * @since 2.0.0
     *
     * @return array $forms Database record objects from form posts.
     */
    public static function get_forms()
    {
        global $wpdb;
        if (
            apply_filters(
                'wpct_is_plugin_active',
                false,
                'contact-form-7/wp-contact-form-7.php'
            )
        ) {
            return $wpdb->get_results(
                "SELECT id, post_title title FROM {$wpdb->prefix}posts WHERE post_type = 'wpcf7_contact_form' AND post_status = 'publish'"
            );
        } elseif (
            apply_filters(
                'wpct_is_plugin_active',
                false,
                'gravityforms/gravityforms.php'
            )
        ) {
            return $wpdb->get_results(
                "SELECT id, title FROM {$wpdb->prefix}gf_form WHERE is_active = 1 AND is_trash = 0"
            );
        }
    }

    /**
     * Register plugin settings.
     *
     * @since 2.0.0
     */
    public function register()
    {
        $host = parse_url(get_bloginfo('url'))['host'];
        //
        // Register general setting
        $this->register_setting(
            'general',
            [
                'notification_receiver' => [
                    'type' => 'string',
                ],
                'backends' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'base_url' => ['type' => 'string'],
                            'headers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'value' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'notification_receiver' => 'admin@' . $host,
                'backends' => [
                    [
                        'name' => 'ERP',
                        'base_url' => 'https://erp.' . $host,
                        'headers' => [
                            [
                                'name' => 'Authorization',
                                'value' => 'Bearer <erp-backend-token>',
                            ],
                        ],
                    ],
                ],
            ]
        );

        // Register REST API setting
        $this->register_setting(
            'rest-api',
            [
                'form_hooks' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'endpoint' => ['type' => 'string'],
                            'pipes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'from' => ['type' => 'string'],
                                        'to' => ['type' => 'string'],
                                        'cast' => [
                                            'type' => 'string',
                                            'enum' => [
                                                'boolean',
                                                'string',
                                                'integer',
                                                'float',
                                                'json',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'form_hooks' => [],
            ]
        );

        // Register RPC API setting
        $this->register_setting(
            'rpc-api',
            [
                'endpoint' => [
                    'type' => 'string',
                ],
                'user' => [
                    'type' => 'string',
                ],
                'password' => [
                    'type' => 'string',
                ],
                'database' => [
                    'type' => 'string',
                ],
                'form_hooks' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'model' => ['type' => 'string'],
                            'pipes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'from' => ['type' => 'string'],
                                        'to' => ['type' => 'string'],
                                        'cast' => [
                                            'type' => 'string',
                                            'enum' => [
                                                'boolean',
                                                'string',
                                                'integer',
                                                'float',
                                                'json',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'endpoint' => '/jsonrpc',
                'user' => 'admin',
                'password' => 'admin',
                'database' => 'erp',
                'form_hooks' => [],
            ]
        );
    }
}
