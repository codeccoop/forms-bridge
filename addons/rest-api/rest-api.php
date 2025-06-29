<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-rest-form-bridge.php';
require_once 'class-rest-form-bridge-template.php';

/**
 * REST API Addon class.
 */
class Rest_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'REST API';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'rest-api';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Rest_Form_Bridge';

    /**
     * Handles the addon's custom form bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Rest_Form_Bridge_Template';

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            static::$api,
            self::default_config(),
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
     * Validate bridge settings. Filters bridges with inconsistencies with
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     * @param array $backends Array with backends data.
     *
     * @return array Array with valid bridge configurations.
     */
    private static function validate_bridges($bridges, $backends)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $backend_names = array_map(function ($backend) {
            return $backend['name'];
        }, $backends);

        $http_methods = static::$bridge_class::allowed_methods;

        $uniques = [];
        $validated = [];
        foreach ($bridges as $bridge) {
            $bridge = self::validate_bridge($bridge, $uniques);

            if (!$bridge) {
                continue;
            }

            if (!in_array($bridge['backend'], $backend_names)) {
                $bridge['backend'] = '';
            }

            if (!in_array($bridge['method'], $http_methods)) {
                $bridge['method'] = 'POST';
            }

            $bridge['endpoint'] = $bridge['endpoint'] ?? '';

            $bridge['is_valid'] =
                $bridge['is_valid'] &&
                !empty($bridge['endpoint']) &&
                !empty($bridge['backend']);

            $validated[] = $bridge;
        }

        return $validated;
    }

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array Ping result.
     */
    protected function do_ping($backend, $request)
    {
        return ['success' => true];
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array Fetched records.
     */
    protected function do_fetch($backend, $endpoint, $request)
    {
        return [];
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields
     * and accepted content type.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array List of fields and content type of the endpoint.
     */
    protected function get_endpoint_schema($backend, $endpoint, $request)
    {
        return [];
    }
}

Rest_Addon::setup();
