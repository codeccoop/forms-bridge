<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-zoho-form-bridge.php';
require_once 'class-zoho-form-bridge-template.php';

require_once 'api-functions.php';

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
     * Handles the zoho oauth service name.
     *
     * @var string
     */
    protected static $zoho_oauth_service = 'ZohoCRM';

    /**
     * Handles the addon's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Zoho_Form_Bridge';

    /**
     * Handles the addon's custom form bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Zoho_Form_Bridge_Template';

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            static::$api,
            self::merge_setting_config([
                'credentials' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'organization_id' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'default' => '',
                            ],
                            'client_id' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'default' => '',
                            ],
                            'client_secret' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'default' => '',
                            ],
                        ],
                        'required' => [
                            'organization_id',
                            'client_id',
                            'client_secret',
                        ],
                    ],
                ],
                'bridges' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'endpoint' => [
                                'type' => 'string',
                                'minLength' => 1,
                            ],
                            'scope' => [
                                'type' => 'string',
                                'minLength' => 1,
                            ],
                        ],
                        'required' => ['credential', 'endpoint', 'scope'],
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
                ['organization_id', 'client_id', 'client_secret'],
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

            $bridge['scope'] = $bridge['scope'] ?? '';
            $bridge['endpoint'] = $bridge['endpoint'] ?? '';

            $bridge['is_valid'] =
                $bridge['is_valid'] &&
                !empty($bridge['endpoint']) &&
                !empty($bridge['scope']) &&
                !empty($bridge['credential']);

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

        $bridge = new static::$bridge_class([
            'backend' => $backend,
            'credential' => $credential['name'],
            'backend' => $backend,
            'endpoint' => '',
            'scope' => '',
            'method' => 'GET',
        ]);

        $success = preg_match('/www\.zohoapis\./', $bridge->backend->base_url);
        return ['success' => $success && $bridge->check_credential()];
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params array $credential Credential data.
     *
     * @return array Fetched records.
     */
    protected function do_fetch($backend, $endpoint, $credential)
    {
        $credentials = self::validate_credentials([$credential]);

        if (empty($credentials)) {
            return [];
        } else {
            $credential = $credentials[0];
        }

        static::temp_register_credentials($credential);

        if (preg_match('/\/users$/', $endpoint)) {
            $scope = static::$zoho_oauth_service . '.users.READ';
        } else {
            $scope = static::$zoho_oauth_service . '.modules.ALL';
        }

        $bridge = new static::$bridge_class([
            'backend' => $backend,
            'credential' => $credential['name'],
            'endpoint' => $endpoint,
            'scope' => $scope,
            'method' => 'GET',
        ]);

        $response = $bridge->submit();
        if (is_wp_error($response)) {
            return [];
        }

        return $response['data'];
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields
     * and accepted content type.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params array $credential Credential data.
     *
     * @return array List of fields and content type of the endpoint.
     */
    protected function get_schema($backend, $endpoint, $credential)
    {
        [$credential] = self::validate_credentials([$credential]);

        if (empty($credential)) {
            return [];
        }

        static::temp_register_credentials($credential);

        $bridge = new static::$bridge_class([
            'backend' => $backend,
            'credential' => $credential['name'],
            'endpoint' => $endpoint,
            'scope' => static::$zoho_oauth_service . '.settings.layouts.READ',
            'method' => 'GET',
        ]);

        return $bridge->api_schema;
    }

    private static function temp_register_credentials($data)
    {
        add_filter(
            'wpct_setting_data',
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

Zoho_Addon::setup();
