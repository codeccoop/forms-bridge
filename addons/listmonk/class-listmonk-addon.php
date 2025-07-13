<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-listmonk-form-bridge.php';
require_once 'hooks.php';

/**
 * Listmonk Addon class.
 */
class Listmonk_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'Listmonk';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'listmonk';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Listmonk_Form_Bridge';

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Backend name.
     * @param null $credential Credential name.
     *
     * @return boolean
     */
    public function ping($backend, $credential = null)
    {
        $bridge = new Listmonk_Form_Bridge(
            [
                'name' => '__listmonk-' . time(),
                'endpoint' => '/api/lists',
                'method' => 'GET',
                'backend' => $backend,
            ],
            self::name
        );

        $response = $bridge->submit();
        return !is_wp_error($response);
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $endpoint API endpoint.
     * @param string $backend Backend name.
     * @param null $credential Credential name.
     *
     * @return array|WP_Error
     */
    public function fetch($endpoint, $backend, $credential = null)
    {
        $bridge = new Listmonk_Form_Bridge(
            [
                'name' => '__listmonk-' . time(),
                'method' => 'GET',
                'endpoint' => $endpoint,
                'backend' => $backend,
            ],
            self::name
        );

        return $bridge->submit();
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields
     * and accepted content type.
     *
     * @param string $endpoint API endpoint.
     * @param string $backend Backend name.
     * @param null $credential Credential name.
     *
     * @return array
     */
    public function get_endpoint_schema($endpoint, $backend, $credential = null)
    {
        if ($endpoint === '/api/subscribers') {
            return [
                [
                    'name' => 'email',
                    'schema' => ['type' => 'string'],
                    'required' => true,
                ],
                [
                    'name' => 'name',
                    'schema' => ['type' => 'string'],
                ],
                [
                    'name' => 'status',
                    'schema' => ['type' => 'string'],
                ],
                [
                    'name' => 'lists',
                    'schema' => [
                        'type' => 'array',
                        'items' => ['type' => 'number'],
                    ],
                ],
                [
                    'name' => 'preconfirm_subscriptions',
                    'schema' => ['type' => 'boolean'],
                ],
                [
                    'name' => 'attribs',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];
        }

        return [];
    }
}

Listmonk_Addon::setup();
