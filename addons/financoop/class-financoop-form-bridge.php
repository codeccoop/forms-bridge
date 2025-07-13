<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the FinanCoop REST API.
 */
class Finan_Coop_Form_Bridge extends Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'financoop';

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
     * Performs an http request to Odoo REST API.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error
     */
    public function submit($payload = [], $attachments = [])
    {
        if (isset($payload['lang']) && $payload['lang'] === 'ca') {
            $payload['lang'] = 'ca_ES';
        }

        if (!empty($payload)) {
            $payload = [
                'jsonrpc' => '2.0',
                'params' => $payload,
            ];
        }

        $response = parent::submit($payload);

        if (is_wp_error($response)) {
            return $response;
        }

        if (isset($response['data']['error'])) {
            return new WP_Error(
                'financoop_rpc_error',
                $response['data']['error']['message'],
                $response['data']['error']['data']
            );
        }

        if (isset($response['data']['result']['error'])) {
            return new WP_Error(
                'financoop_api_error',
                $response['data']['result']['error']['message'],
                $response['data']['result']['error']['data']
            );
        }

        return $response;
    }
}
