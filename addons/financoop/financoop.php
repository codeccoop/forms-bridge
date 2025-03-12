<?php

namespace FORMS_BRIDGE;

use HTTP_BRIDGE\Http_Backend;
use WP_Error;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-financoop-form-bridge.php';
require_once 'class-financoop-form-bridge-template.php';

/**
 * FinanCoop Addon class.
 */
class Finan_Coop_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'FinanCoop';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'financoop';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Finan_Coop_Form_Bridge';

    protected function construct(...$args)
    {
        parent::construct(...$args);

        add_action('rest_api_init', static function () {
            $namespace = REST_Settings_Controller::namespace();
            $version = REST_Settings_Controller::version();

            register_rest_route(
                "{$namespace}/v{$version}",
                '/financoop/campaigns',
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) {
                        return self::fetch_campaigns($request);
                    },
                    'permission_callback' => static function () {
                        return REST_Settings_Controller::permission_callback();
                    },
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                'financoop/campaigns/(<?P<campaign_id>\d+)',
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) {
                        return self::fetch_campaigns($request);
                    },
                    'permission_callback' => static function () {
                        return REST_Settings_Controller::permission_callback();
                    },
                ]
            );
        });
    }

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            self::$api,
            [
                'bridges' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'endpoint' => ['type' => 'string'],
                            'mappers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
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
                                                'csv',
                                                'concat',
                                                'null',
                                            ],
                                        ],
                                    ],
                                    'required' => ['from', 'to', 'cast'],
                                ],
                            ],
                            'template' => ['type' => 'string'],
                        ],
                        'required' => [
                            'name',
                            'backend',
                            'form_id',
                            'endpoint',
                            'mappers',
                        ],
                    ],
                ],
            ],
            [
                'bridges' => [],
            ],
        ];
    }

    /**
     * Apply settings' data validations before db updates.
     *
     * @param array $data Setting data.
     *
     * @return array Validated setting data.
     */
    protected static function validate_setting($data, $setting)
    {
        $data['bridges'] = self::validate_bridges(
            $data['bridges'],
            \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?: []
        );

        return $data;
    }

    /**
     * Validate bridges' settings. Filters bridges with inconsistencies with
     * the current store state.
     *
     * @param array $bridges Array with bridges configurations.
     * @param array $backends Array with backends data.
     *
     * @return array Array with valid bridges configurations.
     */
    private static function validate_bridges($bridges, $backends)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['_id']]);
            },
            []
        );

        $templates = array_map(function ($template) {
            return $template['name'];
        }, apply_filters('forms_bridge_templates', [], 'financoop'));

        $valid_bridges = [];
        for ($i = 0; $i < count($bridges); $i++) {
            $bridge = $bridges[$i];

            // Valid only if backend, form id and template exists
            $is_valid =
                array_reduce(
                    $backends,
                    static function ($is_valid, $backend) use ($bridge) {
                        return $bridge['backend'] === $backend['name'] ||
                            $is_valid;
                    },
                    false
                ) &&
                in_array($bridge['form_id'], $_ids) &&
                (empty($bridge['template']) ||
                    empty($templates) ||
                    in_array($bridge['template'], $templates));

            if ($is_valid) {
                $bridge['mappers'] = array_values(
                    array_filter((array) $bridge['mappers'], function ($pipe) {
                        return !(
                            empty($pipe['from']) ||
                            empty($pipe['to']) ||
                            empty($pipe['cast'])
                        );
                    })
                );

                $valid_bridges[] = $bridge;
            }
        }

        return $valid_bridges;
    }

    private static function get_backend($params)
    {
        if (isset($params['backend'])) {
            $backend = apply_filters(
                'http_bridge_backend',
                null,
                $params['backend']
            );
        } else {
            $base_url = filter_var(
                $params['base_url'] ?? null,
                FILTER_VALIDATE_URL
            );
            $database = sanitize_text_field($params['database'] ?? null);
            $username = sanitize_text_field($params['username'] ?? null);
            $api_key = sanitize_text_field($params['api_key'] ?? null);

            if (
                empty($base_url) ||
                empty($database) ||
                empty($username) ||
                empty($api_key)
            ) {
                return;
            }

            $backend_data = [
                'name' => '__financoop-' . time(),
                'base_url' => $base_url,
                'headers' => [
                    [
                        'name' => 'X-Odoo-Db',
                        'value' => $database,
                    ],
                    [
                        'name' => 'X-Odoo-Username',
                        'value' => $username,
                    ],
                    [
                        'name' => 'X-Odoo-Api-Key',
                        'value' => $api_key,
                    ],
                ],
            ];

            add_filter(
                'wpct_setting_data',
                static function ($setting_data, $name) use ($backend_data) {
                    if ($name !== 'http-bridge_general') {
                        return $setting_data;
                    }

                    $index = array_search(
                        $backend_data['name'],
                        array_column($setting_data['backends'], 'name')
                    );
                    if ($index === false) {
                        $setting_data['backends'][] = $backend_data;
                    }

                    return $setting_data;
                },
                20,
                2
            );

            return new Http_Backend($backend_data['name']);
        }

        return $backend;
    }

    private static function fetch_campaigns($request)
    {
        $params = $request->get_json_params();
        $backend = self::get_backend($params);

        if (empty($backend)) {
            return new WP_Error(
                'bad_request',
                __('Backend is unkown', 'forms-bridge'),
                ['params' => $params]
            );
        }

        $endpoint = '/api/campaign';

        if ($campaign_id = $request['campaign_id']) {
            $endpoint .= '/' . (int) $campaign_id;
        }

        $response = $backend->get($endpoint);

        if (is_wp_error($response)) {
            return $response;
        }

        return $response['data']['data'];
    }
}

Finan_Coop_Addon::setup();
