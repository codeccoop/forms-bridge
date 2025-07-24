<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the Zoho API protocol.
 */
class Zoho_Form_Bridge extends Form_Bridge
{
    /**
     * Performs an http request to the Zoho API backend.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function submit($payload = [], $attachments = [])
    {
        if (!$this->is_valid) {
            return new WP_Error('invalid_bridge');
        }

        $method = $this->method;
        if ($method === 'POST' || $method === 'PUT') {
            $payload = wp_is_numeric_array($payload) ? $payload : [$payload];
            $payload = ['data' => $payload];
        }

        add_filter(
            'http_bridge_backend_headers',
            function ($headers, $backend) {
                if ($backend->name === $this->data['backend']) {
                    if (isset($headers['Authorization'])) {
                        $headers['Authorization'] = str_replace(
                            'Bearer',
                            'Zoho-oauthtoken',
                            $headers['Authorization']
                        );
                    }
                }

                return $headers;
            },
            9,
            2
        );

        $response = $this->backend()->$method(
            $this->endpoint,
            $payload,
            [],
            $attachments
        );

        if (is_wp_error($response)) {
            $data = json_decode(
                $response->get_error_data()['response']['body'],
                true
            );

            $code = $data['data'][0]['code'] ?? null;
            if ($code !== 'DUPLICATE_DATA') {
                return $response;
            }

            $response = $response->get_error_data()['response'];
            $response['data'] = json_decode($response['body'], true);
        }

        return $response;
    }
}
