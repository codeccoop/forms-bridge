<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the Mailchimp API.
 */
class Mailchimp_Form_Bridge extends Rest_Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'mailchimp';

    /**
     * Handles the array of accepted HTTP header names of the bridge API.
     *
     * @var array<string>
     */
    protected static $api_headers = ['Accept', 'Content-Type', 'Authorization'];

    /**
     * Gets bridge's default body encoding schema.
     *
     * @return string|null
     */
    protected function content_type()
    {
        return 'application/json';
    }

    /**
     * Performs an http request to backend's REST API.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    protected function do_submit($payload, $attachments = [])
    {
        $response = parent::do_submit($payload, $attachments);

        if (is_wp_error($response)) {
            // TODO: handle controled errors
        }

        return $response;
    }

    protected function api_schema()
    {
        if (strstr($this->endpoint, '/lists/') !== false) {
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
                $this->endpoint
            );

            $response = $this->patch([
                'name' => 'mailchimp-get-merge-fields',
                'endpoint' => $fields_endpoint,
                'method' => 'GET',
            ])->submit([]);

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

    /**
     * Filters HTTP request args just before it is sent.
     *
     * @param array $request Request arguments.
     *
     * @return array
     */
    public static function do_filter_request($request)
    {
        $headers = &$request['args']['headers'];

        if (empty($headers['Api-Key'])) {
            return $request;
        }

        $basic_auth =
            'Basic ' . base64_encode('forms-bridge:' . $headers['Api-Key']);

        $headers['Authorization'] = $basic_auth;

        return $request;
    }
}
