<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\Settings as BaseSettings;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin settings.
 */
class Settings extends BaseSettings
{
    /**
     * Handle plugin settings rest controller class name.
     *
     * @var string $rest_controller_class Settings REST Controller class name.
     */
    protected static $rest_controller_class = '\FORMS_BRIDGE\REST_Settings_Controller';

    /**
     * Register plugin settings.
     */
    public function register()
    {
        $host = parse_url(get_bloginfo('url'))['host'];

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
                            'method' => [
                                'type' => 'string',
                                'enum' => ['GET', 'POST', 'PUT', 'DELETE'],
                            ],
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
                                                'null',
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
                'endpoint' => ['type' => 'string'],
                'user' => ['type' => 'string'],
                'password' => ['type' => 'string'],
                'database' => ['type' => 'string'],
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
                                                'null',
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
