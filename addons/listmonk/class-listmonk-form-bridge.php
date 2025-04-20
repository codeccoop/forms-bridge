<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the REST API protocol.
 */
class Listmonk_Form_Bridge extends Rest_Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'listmonk';

    /**
     * Handles a custom http origin token to be unseted from headers
     * before submits.
     *
     * @var array<string>
     */
    public const api_headers = ['Accept', 'Content-Type', 'Authorization'];

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
        add_filter(
            'http_request_args',
            '\FORMS_BRIDGE\Listmonk_Form_Bridge::filter_headers',
            10,
            1
        );

        $response = parent::do_submit($payload, $attachments);

        if (is_wp_error($response)) {
            $error_response = $response->get_error_data()['response'];

            if ($error_response['response']['code'] !== 409) {
                return $response;
            }

            if (
                !isset($payload['email']) ||
                $this->endpoint !== '/api/subscribers'
            ) {
                return $response;
            }

            $get_response = $this->patch([
                'name' => 'listmonk-get-subscriber-by-email',
                'method' => 'GET',
            ])->submit([
                'per_page' => '1',
                'query' => "subscribers.email = '{$payload['email']}'",
            ]);

            if (is_wp_error($get_response)) {
                return $response;
            }

            $subscriber_id = $get_response['data']['data']['results'][0]['id'];

            return $this->patch([
                'name' => 'listmonk-update-subscriber',
                'method' => 'PUT',
                'endpoint' => $this->endpoint . '/' . $subscriber_id,
            ])->submit($payload);
        }

        return $response;
    }

    protected function api_schema()
    {
        if ($this->endpoint === '/api/subscribers') {
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

    /**
     * Filter and decoration of default http headers.
     *
     * @param array $request HTTP request args.
     *
     * @return array
     */
    public static function do_filter_request($request)
    {
        $headers = &$request['args']['headers'];

        $user = $headers['Api-User'] ?? null;
        $token = $headers['Token'] ?? null;

        if (empty($user) || empty($token)) {
            return $request;
        }

        $headers['Authorization'] = "token {$user}:{$token}";

        return $request;
    }
}
