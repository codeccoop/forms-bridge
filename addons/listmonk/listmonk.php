<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once FORMS_BRIDGE_ADDONS_DIR . '/rest-api/rest-api.php';

require_once 'class-listmonk-form-bridge.php';
require_once 'class-listmonk-form-bridge-template.php';

/**
 * Listmonk Addon class.
 */
class Listmonk_Addon extends Rest_Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'Listmonk';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'listmonk';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Listmonk_Form_Bridge';

    /**
     * Handles the addon's custom form bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Listmonk_Form_Bridge_Template';

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
        $bridge = new Listmonk_Form_Bridge(
            [
                'name' => '__listmonk-' . time(),
                'endpoint' => '/api/lists',
                'method' => 'GET',
                'backend' => $backend,
            ],
            self::$api
        );

        $response = $bridge->submit([]);
        return ['success' => !is_wp_error($response)];
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @param null $credential Credential data.
     *
     * @return array Fetched records.
     */
    protected function do_fetch($backend, $endpoint, $credential)
    {
        $bridge = new Listmonk_Form_Bridge(
            [
                'name' => '__listmonk-' . time(),
                'method' => 'GET',
                'endpoint' => $endpoint,
                'backend' => $backend,
            ],
            self::$api
        );

        $response = $bridge->submit([]);
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
     * @params null $credential Credential data.
     *
     * @return array List of fields and content type of the endpoint.
     */
    protected function get_schema($backend, $endpoint, $credential)
    {
        $bridge = new Listmonk_Form_Bridge(
            [
                'name' => '__listmonk-' . time(),
                'method' => 'GET',
                'endpoint' => $endpoint,
                'backend' => $backend,
            ],
            self::$api
        );

        return $bridge->api_schema;
    }
}

Listmonk_Addon::setup();
