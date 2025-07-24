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
    private static $request;

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

        add_filter(
            'http_bridge_backend_headers',
            function ($headers, $backend) {
                if ($backend->name === $this->data['backend']) {
                    $credential = $backend->credential;
                    if (!$credential) {
                        return $headers;
                    }

                    [
                        $database,
                        $username,
                        $password,
                    ] = $credential->authorization();
                    $headers['X-Odoo-Db'] = $database;
                    $headers['X-Odoo-Username'] = $username;
                    $headers['X-Odoo-Api-Key'] = $password;
                }

                return $headers;
            },
            10,
            2
        );

        add_filter(
            'http_bridge_request',
            static function ($request) {
                self::$request = $request;
                return $request;
            },
            10,
            1
        );

        $response = parent::submit($payload);

        if (is_wp_error($response)) {
            return $response;
        }

        if (isset($response['data']['error'])) {
            $error = new WP_Error(
                'response_code_' . $response['data']['error']['code'],
                $response['data']['error']['message'],
                $response['data']['error']['data']
            );

            $error_data = ['response' => $response];
            if (self::$request) {
                $error_data['request'] = self::$request;
            }

            $error->add_data($error_data);
            return $error;
        }

        if (isset($response['data']['result']['error'])) {
            $error = new WP_Error(
                'response_code_' . $response['data']['result']['error']['code'],
                $response['data']['result']['error']['message'],
                $response['data']['result']['error']['data']
            );

            $error_data = ['response' => $response];
            if (self::$request) {
                $error_data['request'] = self::$request;
            }

            $error->add_data($error_data);
            return $error;
        }

        $response['data'] = $response['data']['data'] ?? [];
        return $response;
    }
}
