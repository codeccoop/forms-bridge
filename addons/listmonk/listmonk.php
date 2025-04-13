<?php

namespace FORMS_BRIDGE;

use WP_REST_Server;
use HTTP_BRIDGE\Http_Backend;
use WP_Error;

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
     * Addon constructor. Inherits from the abstrac addon constructor and initializes
     * the lists REST API endpoint.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        add_action('rest_api_init', static function () {
            $namespace = REST_Settings_Controller::namespace();
            $version = REST_Settings_Controller::version();

            register_rest_route("{$namespace}/v{$version}", '/listmonk/lists', [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => static function ($request) {
                    $params = $request->get_json_params();
                    return self::fetch_lists($params);
                },
                'permission_callback' => static function () {
                    return REST_Settings_Controller::permission_callback();
                },
            ]);
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

        $params['name'] = $params['name'] ?? '__listmonk-' . time();
        return new Http_Backend($params);
    }

    public static function fetch_lists($backend_params)
    {
        $backend = self::get_backend($backend_params);

        if (empty($backend)) {
            return new WP_Error(
                'bad_request',
                __('Backend is unkown', 'forms-bridge'),
                ['params' => $backend_params]
            );
        }

        $headers = $backend->headers;
        $api_user = $headers['api_user'] ?? null;
        $token = $headers['token'] ?? null;

        if (empty($api_user) || empty($token)) {
            return new WP_Error(
                'unauthorized',
                __('Invalid Listmonk API credentials', 'forms-bridge'),
                ['api_user' => $api_user, 'token' => $token]
            );
        }

        $endpoint = '/api/lists';
        $response = $backend->get(
            $endpoint,
            [],
            [
                'Authorization' => "token {$api_user}:{$token}",
                'Accept' => 'application/json',
            ]
        );

        if (is_wp_error($response)) {
            return $response;
        }

        return $response['data']['data']['results'];
    }
}

Listmonk_Addon::setup();
