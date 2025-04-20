<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the FinanCoop REST API.
 */
class Finan_Coop_Form_Bridge extends Rest_Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'financoop';

    /**
     * Handles allowed HTTP method.
     *
     * @var array
     */
    public const allowed_methods = ['POST'];

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
     * @return array|WP_Error Http request response.
     */
    protected function do_submit($payload, $attachments = [])
    {
        if (isset($payload['lang']) && $payload['lang'] === 'ca') {
            $payload['lang'] = 'ca_ES';
        }

        $response = $this->backend->post($this->endpoint, [
            'jsonrpc' => '2.0',
            'params' => $payload,
        ]);

        $result = Odoo_Form_Bridge::rpc_response($response);

        if (isset($result['error'])) {
            return new WP_Error(
                'financoop_api_error',
                $result['error']['message'],
                [
                    'response' => $response,
                    'payload' => $payload,
                ]
            );
        }

        return $result;
    }
}
