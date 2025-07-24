<?php

namespace FORMS_BRIDGE;

use FBAPI;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-nextcloud-form-bridge.php';
require_once 'hooks.php';

/**
 * Nextcloud Addon class.
 */
class Nextcloud_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'Nextcloud';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'nextcloud';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Nextcloud_Form_Bridge';

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     *
     * @return boolean
     */
    public function ping($backend)
    {
        $backend = FBAPI::get_backend($backend);

        if (!$backend) {
            return false;
        }

        $credential = $backend->credential;
        if (!$credential) {
            return false;
        }

        $response = $backend->get(
            '/remote.php/dav/files/' . rawurlencode($credential->client_id)
        );

        return !is_wp_error($response);
    }

    /**
     * Performs a GET request against the backend model and retrive the response data.
     *
     * @param string $endpoint Target model name.
     * @param string $backend Target backend name.
     *
     * @return array|WP_Error
     */
    public function fetch($endpoint, $backend)
    {
        return [];
    }

    /**
     * Performs an introspection of the backend model and returns API fields
     * and accepted content type.
     *
     * @param string $filepath Filepath.
     * @param string $backend Backend name.
     *
     * @return array List of fields and content type of the model.
     */
    public function get_endpoint_schema($filepath, $backend)
    {
        $bridge = new Nextcloud_Form_Bridge(
            [
                'name' => '__nextcloud-' . time(),
                'endpoint' => $filepath,
                'backend' => $backend,
            ],
            self::name
        );

        $headers = $bridge->table_headers();
        if (is_wp_error($headers)) {
            return [];
        }

        $fields = [];
        foreach ($headers as $header) {
            $fields[] = [
                'name' => $header,
                'schema' => ['type' => 'string'],
            ];
        }

        return $fields;
    }
}

Nextcloud_Addon::setup();

add_filter('http_request_args', function ($args) {
    $args['timeout'] = 30;
    return $args;
});
