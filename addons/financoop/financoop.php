<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once FORMS_BRIDGE_ADDONS_DIR . '/rest-api/rest-api.php';

require_once 'class-financoop-form-bridge.php';
require_once 'class-financoop-form-bridge-template.php';

require_once 'shortcodes.php';

/**
 * FinanCoop Addon class.
 */
class Finan_Coop_Addon extends Rest_Addon
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

    /**
     * Handles the addon's custom form bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Finan_Coop_Form_Bridge_Template';

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     * @params null $credential Credential data.
     *
     * @return array Ping result.
     */
    protected function do_ping($backend, $credential = null)
    {
        $bridge = new Finan_Coop_Form_Bridge([
            'name' => '__financoop-' . time(),
            'endpoint' => '/api/campaign',
            'method' => 'GET',
            'backend' => $backend,
        ]);

        $response = $bridge->submit([]);
        return ['success' => !is_wp_error($response)];
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params null $credential Credential data.
     *
     * @return array Fetched records.
     */
    protected function do_fetch($backend, $endpoint, $credential = null)
    {
        $bridge = new Finan_Coop_Form_Bridge([
            'name' => '__financoop-' . time(),
            'endpoint' => $endpoint,
            'backend' => $backend,
            'method' => 'GET',
        ]);

        $response = $bridge->submit([]);
        if (is_wp_error($response)) {
            return [];
        }

        return $response['data']['data'];
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
    protected function get_schema($backend, $endpoint, $credential = null)
    {
        $bridge = new Finan_Coop_Form_Bridge([
            'name' => '__financoop-' . time(),
            'endpoint' => $endpoint,
            'backend' => $backend,
            'method' => 'GET',
        ]);

        return $bridge->api_schema;
    }
}

Finan_Coop_Addon::setup();
