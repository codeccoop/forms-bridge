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
        $backend = $this->backend;

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

        $response = $backend->post($this->endpoint, $payload, [
            'Authorization' => "token {$api_user}:{$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

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

            $get_response = $backend->get(
                $this->endpoint,
                [
                    'per_page' => '1',
                    'query' => "subscribers.email = '{$payload['email']}'",
                ],
                [
                    'Authorization' => "token {$api_user}:{$token}",
                    'Accept' => 'application/json',
                ]
            );

            if (is_wp_error($get_response)) {
                return $response;
            }

            $subscriber_id = $get_response['data']['data']['results'][0]['id'];

            return $backend->put(
                $this->endpoint . '/' . $subscriber_id,
                $payload,
                [
                    'Authorization' => "token {$api_user}:{$token}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            );
        }

        return $response;
    }

    protected function api_fields()
    {
        if (
            $this->endpoint === '/api/subscribers' &&
            $this->method === 'POST'
        ) {
            return [
                'email',
                'name',
                'status',
                'lists',
                'list_uuids',
                'preconfirm_subscriptions',
                'attribs',
            ];
        }

        return [];
    }
}
