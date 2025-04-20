<?php

namespace FORMS_BRIDGE;

use HTTP_BRIDGE\Http_Backend;
use WP_Error;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit();
}

require_once FORMS_BRIDGE_ADDONS_DIR . '/rest-api/rest-api.php';

require_once 'class-financoop-form-bridge.php';
require_once 'class-financoop-form-bridge-template.php';

/**
 * FinanCoop Addon class.
 */
class Finan_Coop_Addon extends Rest_Addon
{
    private const http_headers = [
        'X-Odoo-Db',
        'X-Odoo-Username',
        'X-Odoo-Api-Key',
    ];

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
     * Addon constructor. Inherits from the abstract addon and initializes the
     * addon's rest api.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        add_action('rest_api_init', static function () {
            $namespace = REST_Settings_Controller::namespace();
            $version = REST_Settings_Controller::version();

            register_rest_route(
                "{$namespace}/v{$version}",
                '/financoop/campaigns',
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) {
                        $params = $request->get_json_params();
                        return self::fetch_campaigns($params);
                    },
                    'permission_callback' => static function () {
                        return REST_Settings_Controller::permission_callback();
                    },
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                'financoop/campaigns/(<?P<campaign_id>\d+)',
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) {
                        $campaign_id = $request['campaign_id'];
                        $params = $request->get_json_params();

                        return self::fetch_campaign($campaign_id, $params);
                    },
                    'permission_callback' => static function () {
                        return REST_Settings_Controller::permission_callback();
                    },
                    'args' => [],
                ]
            );
        });
    }

    /**
     * Backend instance getter. If backend isn't registered, add a
     * ephemeral entry on the backends registry.
     *
     * @param array $params Backend data.
     *
     * @return Http_Backend
     */
    private static function get_backend($params)
    {
        if (isset($params['name'])) {
            $backend = apply_filters(
                'http_bridge_backend',
                null,
                $params['name']
            );

            if ($backend) {
                return $backend;
            }
        }

        $base_url = filter_var(
            $params['base_url'] ?? null,
            FILTER_VALIDATE_URL
        );

        if (!$base_url) {
            return;
        }

        $params['name'] = $params['name'] ?? '__financoop-' . time();
        return new Http_Backend($params);
    }

    public static function fetch_campaign($campaign_id, $backend_params)
    {
        $backend = self::get_backend($backend_params);

        if (empty($backend)) {
            return new WP_Error(
                'bad_request',
                __('Backend is unkown', 'forms-bridge'),
                ['params' => $backend_params]
            );
        }

        $endpoint = '/api/campaign/' . intval($campaign_id);

        $response = $backend->get($endpoint);

        if (is_wp_error($response)) {
            return $response;
        }

        return $response['data']['data'];
    }

    public static function fetch_campaigns($backend_params)
    {
        $backend = self::get_backend($backend_params);

        if (empty($backend)) {
            return new WP_Error(
                'bad_request',
                __('Backend is unkown', 'forms-bridge'),
                ['params' => $backend_params]
            );
        }

        $endpoint = '/api/campaign';

        $response = $backend->get($endpoint);

        if (is_wp_error($response)) {
            return $response;
        }

        return array_values(
            array_filter($response['data']['data'], static function (
                $campaign
            ) {
                // @todo allow draft for preview purpose but with some kind of warnings
                return $campaign['state'] === 'open'; // $campaign['state'] !== 'closed' && $campaign['state'] !== 'draft';
            })
        );
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
        $bridge = new Finan_Coop_Form_Bridge([
            'name' => '__financoop-' . time(),
            'endpoint' => '/api/campaigns',
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
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array Fetched records.
     */
    protected function do_fetch($backend, $endpoint, $request)
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
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array List of fields and content type of the endpoint.
     */
    protected function get_schema($backend, $endpoint, $request)
    {
        $bridge = new Finan_Coop_Form_Bridge([
            'name' => '__financoop-' . time(),
            'endpoint' => $endpoint,
            'backend' => $backend,
            'method' => 'GET',
        ]);

        return $bridge->api_fields;
    }
}

Finan_Coop_Addon::setup();
