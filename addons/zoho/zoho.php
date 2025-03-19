<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-zoho-credential.php';
require_once 'class-zoho-form-bridge.php';
require_once 'class-zoho-form-bridge-template.php';

/**
 * Zoho Addon class.
 */
class Zoho_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'Zoho';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'zoho';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Zoho_Form_Bridge';

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
            'forms_bridge_zoho_credentials',
            static function ($credentials) {
                if (!wp_is_numeric_array($credentials)) {
                    $credentials = [];
                }

                return array_merge($credentials, self::credentials());
            },
            10,
            1
        );

        add_filter(
            'forms_bridge_zoho_credential',
            static function ($credential, $name) {
                if ($credential instanceof Zoho_Credential) {
                    return $credential;
                }

                $credentials = self::credentials();
                foreach ($credentials as $credential) {
                    if ($credential->name === $name) {
                        return $credential;
                    }
                }
            },
            10,
            2
        );
    }

    /**
     * Addon credentials' instances getter.
     *
     * @return array List with available credentials instances.
     */
    private static function credentials()
    {
        return array_map(
            static function ($data) {
                return new Zoho_Credential($data);
            },
            self::setting()->credentials ?: []
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
                'credentials' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'organization_id' => ['type' => 'string'],
                            'client_id' => ['type' => 'string'],
                            'client_secret' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                        ],
                        'required' => [
                            'name',
                            'organization_id',
                            'client_id',
                            'client_secret',
                            'backend',
                        ],
                    ],
                ],
                'bridges' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'credential' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'endpoint' => ['type' => 'string'],
                            'scope' => ['type' => 'string'],
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
                            'credential',
                            'form_id',
                            'endpoint',
                            'scope',
                            'mappers',
                            'is_valid',
                        ],
                    ],
                ],
            ],
            [
                'credentials' => [],
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

        $data['credentials'] = self::validate_credentials(
            $data['credentials'],
            $backends
        );

        $data['bridges'] = self::validate_bridges(
            $data['bridges'],
            $data['credentials']
        );

        return $data;
    }

    /**
     * Credentials setting field validation. Filters inconsistent keys
     * based on the Http_Bridge's backends store state.
     *
     * @param array $credentials Collection of credentials data.
     * @param array $backends
     *
     * @return array Validated credentials.
     */
    private static function validate_credentials($credentials, $backends)
    {
        if (!wp_is_numeric_array($credentials)) {
            return [];
        }

        $backend_names = array_map(function ($backend) {
            return $backend['name'];
        }, $backends);

        $uniques = [];
        $validated = [];
        foreach ($credentials as $credential) {
            if (empty($credential['name'])) {
                continue;
            }

            if (in_array($credential['name'], $uniques)) {
                continue;
            }

            if (!in_array($credential['backend'] ?? null, $backend_names)) {
                $credential['backend'] = '';
            }

            $credential['organization_id'] =
                $credential['organization_id'] ?? '';
            $credential['client_id'] = $credential['client_id'] ?? '';
            $credential['client_secret'] = $credential['client_secret'] ?? '';

            $validated[] = $credential;
            $uniques[] = $credential['name'];
        }

        return $validated;
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     * @param array $credentials Array with credentials data.
     *
     * @return array Array with valid bridge configurations.
     */
    private static function validate_bridges($bridges, $credentials)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $form_ids = array_map(function ($form) {
            return $form['_id'];
        }, apply_filters('forms_bridge_forms', []));

        $credentials = array_map(function ($credential) {
            return $credential['name'];
        }, $credentials);

        $uniques = [];
        $validated = [];
        foreach ($bridges as $bridge) {
            if (empty($bridge['name'])) {
                continue;
            }

            if (in_array($bridge['name'], $uniques)) {
                continue;
            } else {
                $uniques[] = $bridge['name'];
            }

            if (!in_array($bridge['form_id'] ?? null, $form_ids)) {
                $bridge['form_id'] = '';
            }

            if (!in_array($bridge['credential'] ?? null, $credentials)) {
                $bridge['credential'] = '';
            }

            $bridge['scope'] = $bridge['scope'] ?? '';
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

Zoho_Addon::setup();
