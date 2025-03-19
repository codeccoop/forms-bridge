<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-dolibarr-api-key.php';
require_once 'class-dolibarr-form-bridge.php';
require_once 'class-dolibarr-form-bridge-template.php';

require_once 'country-codes.php';
// require_once 'state-codes.php';

/**
 * Dolibarr Addon class.
 */
class Dolibarr_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'Dolibarr';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'dolibarr';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Dolibarr_Form_Bridge';

    /**
     * Addon constructor. Inherits from the abstract addon and initialize interceptos
     * and custom hooks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);
        self::custom_hooks();
    }

    /**
     * Addon custom hooks.
     */
    private static function custom_hooks()
    {
        add_filter(
            'forms_bridge_dolibarr_api_keys',
            static function ($api_keys) {
                if (!wp_is_numeric_array($api_keys)) {
                    $api_keys = [];
                }

                return array_merge($api_keys, self::api_keys());
            },
            10,
            1
        );

        add_filter(
            'forms_bridge_dolibarr_api_key',
            static function ($api_key, $name) {
                if ($api_key instanceof Dolibarr_API_Key) {
                    return $api_key;
                }

                $api_keys = self::api_keys();
                foreach ($api_keys as $api_key) {
                    if ($api_key->name === $name) {
                        return $api_key;
                    }
                }
            },
            10,
            2
        );
    }

    /**
     * Addon api keys instances getter.
     *
     * @return array List with available api keys instances.
     */
    private static function api_keys()
    {
        return array_map(
            static function ($data) {
                return new Dolibarr_API_Key($data);
            },
            self::setting()->api_keys ?: []
        );
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
                'api_keys' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'key' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                        ],
                        'required' => ['name', 'key', 'backend'],
                    ],
                ],
                'bridges' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'api_key' => ['type' => 'string'],
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
                            'is_valid' => ['type' => 'boolean'],
                        ],
                        'required' => [
                            'name',
                            'api_key',
                            'form_id',
                            'endpoint',
                            'mappers',
                            'is_valid',
                        ],
                    ],
                ],
            ],
            [
                'api_keys' => [],
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
        $backends =
            \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?: [];

        $data['api_keys'] = self::validate_api_keys(
            $data['api_keys'],
            $backends
        );

        $data['bridges'] = self::validate_bridges(
            $data['bridges'],
            $data['api_keys']
        );

        return $data;
    }

    /**
     * API keys setting field validation. Filters inconsistent keys
     * based on the Http_Bridge's backends store state.
     *
     * @param array $api_keys Collection of api key arrays.
     * @param array $backends
     *
     * @return array Validated API keys.
     */
    private static function validate_api_keys($api_keys, $backends)
    {
        if (!wp_is_numeric_array($api_keys)) {
            return [];
        }

        $backend_names = array_map(function ($backend) {
            return $backend['name'];
        }, $backends);

        $uniques = [];
        $validated = [];
        foreach ($api_keys as $api_key) {
            if (empty($api_key['name'])) {
                continue;
            }

            if (in_array($api_key['name'], $uniques)) {
                continue;
            }

            if (!in_array($api_key['backend'] ?? null, $backend_names)) {
                $api_key['backend'] = '';
            }

            $api_key['key'] = $api_key['key'] ?? '';

            $validated[] = $api_key;
            $uniques[] = $api_key['name'];
        }

        return $validated;
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     * @param array $api_keys Array with API keys data.
     *
     * @return array Validated bridge configurations.
     */
    private static function validate_bridges($bridges, $api_keys)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $form_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['_id']]);
            },
            []
        );

        $key_names = array_map(function ($api_key) {
            return $api_key['name'];
        }, $api_keys);

        $uniques = [];
        $validated = [];
        foreach ($bridges as $bridge) {
            if (empty($bridge['name'])) {
                continue;
            }

            if (in_array($bridge['name'], $uniques, true)) {
                continue;
            } else {
                $uniques[] = $bridge['name'];
            }

            if (!in_array($bridge['api_key'], $key_names, true)) {
                $bridge['api_key'] = '';
            }

            if (!in_array($bridge['form_id'], $form_ids, true)) {
                $bridge['form_id'] = '';
            }

            $bridge['endpoint'] = $bridge['endpoint'] ?? '';

            $bridge['mappers'] = array_values(
                array_filter((array) $bridge['mappers'], function ($pipe) {
                    return !(
                        empty($pipe['from']) ||
                        empty($pipe['to']) ||
                        empty($pipe['cast'])
                    );
                })
            );

            $is_valid = true;
            unset($bridge['is_valid']);
            foreach ($bridge as $field => $value) {
                if ($field === 'mappers') {
                    continue;
                }

                $is_valid = $is_valid && !empty($value);
            }

            $bridge['is_valid'] = $is_valid;
            $validated[] = $bridge;
        }

        return $validated;
    }
}

Dolibarr_Addon::setup();
