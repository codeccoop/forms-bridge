<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-mailchimp-form-bridge.php';
require_once 'hooks.php';

/**
 * Mapchimp Addon class.
 */
class Mailchimp_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'Mailchimp';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'mailchimp';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Mailchimp_Form_Bridge';

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     *
     * @return boolean
     */
    public function ping($backend)
    {
        $bridge = new Mailchimp_Form_Bridge(
            [
                'name' => '__mailchimp-' . time(),
                'endpoint' => '/3.0/lists',
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
     *
     * @return array|WP_Error
     */
    public function fetch($endpoint, $backend)
    {
        $bridge = new Mailchimp_Form_Bridge(
            [
                'name' => '__mailchimp-' . time(),
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
     *
     * @return array
     */
    public function get_endpoint_schema($endpoint, $backend)
    {
        if (strstr($endpoint, '/lists/') !== false) {
            $fields = [
                [
                    'name' => 'email_address',
                    'schema' => ['type' => 'string'],
                    'required' => true,
                ],
                [
                    'name' => 'status',
                    'schema' => ['type' => 'string'],
                    'required' => true,
                ],
                [
                    'name' => 'email_type',
                    'schema' => ['type' => 'string'],
                ],
                [
                    'name' => 'interests',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
                [
                    'name' => 'language',
                    'schema' => ['type' => 'string'],
                ],
                [
                    'name' => 'vip',
                    'schema' => ['type' => 'boolean'],
                ],
                [
                    'name' => 'location',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'latitude' => ['type' => 'number'],
                            'longitude' => ['type' => 'number'],
                        ],
                    ],
                ],
                [
                    'name' => 'marketing_permissions',
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'marketing_permission_id' => [
                                    'type' => 'string',
                                ],
                                'enabled' => ['type' => 'boolean'],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'ip_signup',
                    'schema' => ['type' => 'string'],
                ],
                [
                    'name' => 'ip_opt',
                    'schema' => ['type' => 'string'],
                ],
                [
                    'name' => 'timestamp_opt',
                    'schema' => ['type' => 'string'],
                ],
                [
                    'name' => 'tags',
                    'schema' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
                [
                    'name' => 'merge_fields',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];

            $fields_endpoint = str_replace(
                '/members',
                '/merge-fields',
                $endpoint
            );

            $bridge = new Mailchimp_Form_Bridge(
                [
                    'name' => '__mailchimp-' . time(),
                    'endpoint' => $fields_endpoint,
                    'method' => 'GET',
                    'backend' => $backend,
                ],
                self::name
            );

            $response = $bridge->submit();

            if (is_wp_error($response)) {
                return [];
            }

            foreach ($response['data']['merge_fields'] as $field) {
                $fields[] = [
                    'name' => 'merge_fields.' . $field['tag'],
                    'schema' => ['type' => 'string'],
                ];
            }

            return $fields;
        }

        return [];
    }
}

Mailchimp_Addon::setup();
