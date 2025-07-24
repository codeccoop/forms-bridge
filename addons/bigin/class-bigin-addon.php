<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once FORMS_BRIDGE_ADDONS_DIR . '/zoho/class-zoho-addon.php';

require_once 'class-bigin-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * Bigin Addon class.
 */
class Bigin_Addon extends Zoho_Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'Bigin';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'bigin';

    /**
     * Handles the zoho oauth service name.
     *
     * @var string
     */
    protected const zoho_oauth_service = 'ZohoBigin';

    /**
     * Handles the addon's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Bigin_Form_Bridge';

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Backend name.
     *
     * @return boolean
     */
    public function ping($backend)
    {
        $bridge_class = static::bridge_class;
        $bridge = new $bridge_class(
            [
                'name' => '__bigin-' . time(),
                'backend' => $backend,
                'endpoint' => '/bigin/v2/users',
                'method' => 'GET',
            ],
            static::name
        );

        $backend = $bridge->backend;
        if (!$backend) {
            return false;
        }

        $credential = $backend->credential;
        if (!$credential) {
            return false;
        }

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

        $response = $bridge->submit(['type' => 'CurrentUser']);
        return !is_wp_error($response);
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields.
     *
     * @param string $endpoint API endpoint.
     * @param string $backend Backend name.
     *
     * @return array List of fields and content type of the endpoint.
     */
    public function get_endpoint_schema($endpoint, $backend)
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

        $module = $matches[2];

        $bridge_class = self::bridge_class;
        $bridge = new $bridge_class(
            [
                'name' => '__bigin-' . time(),
                'backend' => $backend,
                'endpoint' => '/bigin/v2/settings/layouts',
                'method' => 'GET',
            ],
            self::name
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

Bigin_Addon::setup();
