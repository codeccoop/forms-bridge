<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-zoho-form-bridge.php';
require_once 'class-zoho-credential.php';
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

    public const credential_class = '\FORMS_BRIDGE\Zoho_Credential';

    /**
     * Credential data sanitization.
     *
     * @param array $credential Credential data.
     * @param array $schema Credential schema.
     *
     * @return array
     */
    protected static function sanitize_credential($credential, $schema)
    {
        $credential = parent::sanitize_credential($credential, $schema);
        if (!$credential) {
            return;
        }

        if ($credential['type'] === 'Server-based') {
            $credential['organization_id'] = '';
            $is_valid =
                !empty($credential['access_token']) &&
                !empty($credential['refresh_token']) &&
                !empty($credential['expires_at']);
        } else {
            $credential['refresh_token'] = '';
            $is_valid =
                !empty($credential['organization_id']) &&
                !empty($credential['access_token']) &&
                !empty($credential['expires_at']);
        }

        $credential['is_valid'] = $credential['is_valid'] && $is_valid;
        $credential['enabled'] =
            $credential['enabled'] && $credential['is_valid'];

        return $credential;
    }

    /**
     * Bridge data sanitization.
     *
     * @param array $bridge Bridge data.
     * @param array $setting_data Addon setting data.
     *
     * @return array
     */
    protected static function sanitize_bridge($bridge, $setting_data)
    {
        $bridge = parent::sanitize_bridge($bridge, $setting_data);
        $bridge['is_valid'] = $bridge['is_valid'] && !empty($bridge['scope']);
        return $bridge;
    }

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Backend name.
     * @param string $credential Credential name.
     *
     * @return boolean
     */
    public function ping($backend, $credential = null)
    {
        $bridge_class = static::bridge_class;
        $bridge = new $bridge_class(
            [
                'name' => '__zoho-' . time(),
                'credential' => $credential,
                'backend' => $backend,
                'endpoint' => '/',
                'method' => 'GET',
            ],
            static::name
        );

        $credential = $bridge->credential;
        if (!$credential) {
            return false;
        }

        $backend = $bridge->backend;

        $parsed = wp_parse_url($backend->base_url);
        $host = $parsed['host'] ?? '';

        if (
            !preg_match(
                '/www\.zohoapis\.(\w{2,3}(\.\w{2})?)$/',
                $host,
                $matches
            )
        ) {
            return false;
        }

        $region = $matches[1];
        if (!preg_match('/' . $region . '$/', $credential->region)) {
            return false;
        }

        $access_token = $credential->get_access_token($backend);
        return !!$access_token;
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $endpoint API endpoint.
     * @param string $backend Backend name.
     * @param string $credential Credential name.
     *
     * @return array|WP_Error
     */
    public function fetch($endpoint, $backend, $credential = null)
    {
        $bridge_class = static::bridge_class;
        $bridge = new $bridge_class(
            [
                'name' => '__zoho-' . time(),
                'backend' => $backend,
                'credential' => $credential,
                'endpoint' => $endpoint,
                'method' => 'GET',
            ],
            static::name
        );

        return $bridge->submit();
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields.
     *
     * @param string $endpoint API endpoint.
     * @param string $backend Backend name.
     * @param string $credential Credential name.
     *
     * @return array List of fields and content type of the endpoint.
     */
    public function get_endpoint_schema($endpoint, $backend, $credential = null)
    {
        if (
            !preg_match(
                '/\/(([A-Z][a-z]+(_[A-Z][a-z])?)(?:\/upsert)?$)/',
                $endpoint,
                $matches
            )
        ) {
            return [];
        }

        $module = $matches[1];

        $bridge_class = static::bridge_class;
        $bridge = new $bridge_class(
            [
                'name' => '__zoho-' . time(),
                'backend' => $backend,
                'credential' => $credential,
                'endpoint' => '/crm/v7/settings/layouts',
                'method' => 'GET',
            ],
            static::name
        );

        $response = $bridge->submit(['module' => $module]);

        if (is_wp_error($response)) {
            return [];
        }

        $fields = [];
        foreach ($response['data']['layouts'] as $layout) {
            foreach ($layout['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    $type = $field['json_type'];
                    if ($type === 'jsonobject') {
                        $type = 'object';
                    } elseif ($type === 'jsonarray') {
                        $type = 'array';
                    } elseif ($type === 'double') {
                        $type = 'number';
                    }

                    $fields[] = [
                        'name' => $field['api_name'],
                        'schema' => ['type' => $type],
                    ];
                }
            }
        }

        return $fields;
    }
}

Zoho_Addon::setup();
