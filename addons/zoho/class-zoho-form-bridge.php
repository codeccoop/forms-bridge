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
     * Returns json as static bridge content type.
     *
     * @return string.
     */
    protected function content_type()
    {
        return 'application/json';
    }

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
        $credential = $this->credential();
        if (!$credential) {
            return new WP_Error('unauthorized');
        }

        $access_token = $credential->get_access_token();
        if (empty($access_token)) {
            return new WP_Error(
                'unauthorized',
                __('OAuth invalid response', 'forms-bridge')
            );
        }

        $method_fn = strtolower($this->method);
        if ($method_fn === 'post' || $method_fn === 'put') {
            $payload = wp_is_numeric_array($payload) ? $payload : [$payload];
            $payload = ['data' => $payload];
        }

        $response = $this->backend->$method_fn(
            $this->endpoint,
            $payload,
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
            ],
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
