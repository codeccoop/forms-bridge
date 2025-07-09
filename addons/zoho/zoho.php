<?php

namespace FORMS_BRIDGE;

use HTTP_BRIDGE\Http_Backend;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-zoho-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * Zoho Addon class.
 */
class Zoho_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'Zoho';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'zoho';

    /**
     * Handles the zoho oauth service name.
     *
     * @var string
     */
    protected const zoho_oauth_service = 'ZohoCRM';

    /**
     * Handles the addon's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Zoho_Form_Bridge';

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    public static function schema()
    {
        $schema = parent::schema();

        $schema['credentials'] = [
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
                'required' => ['organization_id', 'client_id', 'client_secret'],
            ],
        ];

        return $schema;
    }

    /**
     * Apply settings' data validations before db updates.
     *
     * @param array $data Setting data.
     *
     * @return array Validated setting data.
     */
    protected static function sanitize_setting($data)
    {
        $data['credentials'] = self::sanitize_credentials($data['credentials']);

        $data['bridges'] = self::sanitize_bridges(
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
    private static function sanitize_credentials($credentials)
    {
        if (!wp_is_numeric_array($credentials)) {
            return [];
        }

        $uniques = [];
        $validated = [];
        foreach ($credentials as $credential) {
            $credential = self::sanitize_credential(
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
    private static function sanitize_bridges($bridges, $credentials)
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
            $bridge = self::sanitize_bridge($bridge, $uniques);

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
        [$credential] = self::sanitize_credentials([$credential]);

        if (empty($credential)) {
            return ['success' => false];
        }

        static::temp_register_credentials($credential);

        $bridge_class = static::bridge_class;
        $bridge = new $bridge_class([
            'name' => '__zoho-' . time(),
            'credential' => $credential['name'],
            'backend' => $backend,
            'endpoint' => '/',
            'scope' => '',
            'method' => 'GET',
        ]);

        $backend = $bridge->backend;
        $success =
            $backend instanceof Http_Backend &&
            strstr($backend->base_url, 'www.zohoapis.') !== false;
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
        $credentials = self::sanitize_credentials([$credential]);

        if (empty($credentials)) {
            return [];
        } else {
            $credential = $credentials[0];
        }

        static::temp_register_credentials($credential);

        if (preg_match('/\/users$/', $endpoint)) {
            $scope = static::zoho_oauth_service . '.users.READ';
        } else {
            $scope = static::zoho_oauth_service . '.modules.ALL';
        }

        $bridge_class = static::bridge_class;
        $bridge = new $bridge_class([
            'name' => '__zoho-' . time(),
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
    protected function get_endpoint_schema($backend, $endpoint, $credential)
    {
        [$credential] = self::sanitize_credentials([$credential]);

        if (empty($credential)) {
            return [];
        }

        static::temp_register_credentials($credential);

        $bridge_class = static::bridge_class;
        $bridge = new $bridge_class([
            'name' => '__zoho-' . time(),
            'backend' => $backend,
            'credential' => $credential['name'],
            'endpoint' => $endpoint,
            'scope' => static::zoho_oauth_service . '.settings.layouts.READ',
            'method' => 'GET',
        ]);

        return $bridge->endpoint_schema;
    }

    private static function temp_register_credentials($credential_data)
    {
        Settings_Store::use_getter(static::name, function ($data) use (
            $credential_data
        ) {
            foreach ($data['credentials'] as $candidate) {
                if ($candidate['name'] === $credential_data['name']) {
                    $credential = $candidate;
                    break;
                }

                if (!isset($credential)) {
                    $setting['credentials'][] = $credential_data;
                }

                return $data;
            }
        });
    }
}

Zoho_Addon::setup();
