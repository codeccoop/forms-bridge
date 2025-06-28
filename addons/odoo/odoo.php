<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-odoo-form-bridge.php';
require_once 'class-odoo-form-bridge-template.php';

require_once 'api-functions.php';

/**
 * Odoo Addon class.
 */
class Odoo_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'Odoo';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'odoo';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Odoo_Form_Bridge';

    /**
     * Handles the addon's custom bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Odoo_Form_Bridge_Template';

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            self::$api,
            self::merge_setting_config([
                'credentials' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'database' => ['type' => 'string'],
                            'user' => ['type' => 'string'],
                            'password' => ['type' => 'string'],
                        ],
                        'required' => ['name', 'database', 'user', 'password'],
                    ],
                ],
                'bridges' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'credential' => ['type' => 'string'],
                            'endpoint' => ['type' => 'string'],
                        ],
                        'required' => ['credential', 'endpoint'],
                    ],
                ],
            ]),
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
     * @param Setting $setting Setting instance.
     *
     * @return array Validated setting data.
     */
    protected static function validate_setting($data, $setting)
    {
        $data['credentials'] = self::validate_credentials($data['credentials']);

        $data['bridges'] = self::validate_bridges(
            $data['bridges'],
            $data['credentials']
        );

        return $data;
    }

    /**
     * Credentials setting field validation.
     *
     * @param array $credentials Collection of credentials data.
     *
     * @return array Validated credentials.
     */
    private static function validate_credentials($credentials)
    {
        if (!wp_is_numeric_array($credentials)) {
            return [];
        }

        $uniques = [];
        $validated = [];
        foreach ($credentials as $credential) {
            $credential = self::validate_credential(
                $credential,
                ['database', 'user', 'password'],
                $uniques
            );

            if (empty($credential)) {
                continue;
            }

            $validated[] = $credential;
        }

        return $validated;
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with the
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     * @param array $credentials Array with credentials data.
     *
     * @return array Validated bridge configurations.
     */
    private static function validate_bridges($bridges, $credentials)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $credentials = array_map(function ($credential) {
            return $credential['name'];
        }, $credentials);

        $uniques = [];
        $validated = [];
        foreach ($bridges as $bridge) {
            $bridge = self::validate_bridge($bridge, $uniques);

            if (!$bridge) {
                continue;
            }

            if (!in_array($bridge['credential'] ?? null, $credentials)) {
                $bridge['credential'] = '';
            }

            /* context: endpoint is an alias for the db model */
            $bridge['endpoint'] = $bridge['endpoint'] ?? '';

            $bridge['is_valid'] =
                $bridge['is_valid'] &&
                !empty($bridge['credential']) &&
                !empty($bridge['endpoint']);

            $validated[] = $bridge;
        }

        return $validated;
    }

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     * @params array $credential Credential data.
     *
     * @return array Ping result.
     */
    protected function do_ping($backend, $credential)
    {
        [$credential] = self::validate_credentials([$credential]);

        if (empty($credential)) {
            return ['success' => false];
        }

        static::temp_register_credentials($credential);

        $bridge = new Odoo_Form_Bridge([
            'method' => 'search',
            'endpoint' => 'res.users',
            'credential' => $credential['name'],
            'backend' => $backend,
        ]);

        $response = $bridge->submit([]);
        return ['success' => !is_wp_error($response)];
    }

    /**
     * Performs a GET request against the backend model and retrive the response data.
     *
     * @param string $backend Target backend name.
     * @param string $model Target model name.
     * @params array $credential Credential data.
     *
     * @return array Fetched records.
     */
    protected function do_fetch($backend, $model, $credential)
    {
        [$credential] = self::validate_credentials([$credential]);

        if (empty($credential)) {
            return [];
        }

        static::temp_register_credentials($credential);

        $bridge = new Odoo_Form_Bridge([
            'method' => 'search_read',
            'endpoint' => $model,
            'backend' => $backend,
            'credential' => $credential['name'],
        ]);

        $response = $bridge->submit([], ['id', 'name']);
        if (is_wp_error($response)) {
            return [];
        }

        return $response['data']['result'];
    }

    /**
     * Performs an introspection of the backend model and returns API fields
     * and accepted content type.
     *
     * @param string $backend Target backend name.
     * @param string $model Target model name.
     * @params array $credential Credential data.
     *
     * @return array List of fields and content type of the model.
     */
    protected function get_schema($backend, $model, $credential)
    {
        [$credential] = self::validate_credentials([$credential]);

        if (empty($credential)) {
            return [];
        }

        static::temp_register_credentials($credential);

        $bridge = new Odoo_Form_Bridge([
            'method' => 'get_fields',
            'endpoint' => $model,
            'backend' => $backend,
            'credential' => $credential['name'],
        ]);

        return $bridge->api_schema;
    }

    private static function temp_register_credentials($data)
    {
        add_filter(
            'wpct_plugin_setting_data',
            static function ($setting, $setting_name) use ($data) {
                if ($setting_name === 'forms-bridge_' . static::$api) {
                    foreach ($setting['credentials'] as $candidate) {
                        if ($candidate['name'] === $data['name']) {
                            $credential = $candidate;
                            break;
                        }
                    }

                    if (!isset($credential)) {
                        $setting['credentials'][] = $data;
                    }
                }

                return $setting;
            },
            10,
            2
        );
    }
}

Odoo_Addon::setup();
