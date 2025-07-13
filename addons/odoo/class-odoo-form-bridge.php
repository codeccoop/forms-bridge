<?php

namespace FORMS_BRIDGE;

use HTTP_BRIDGE\Http_Client;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implementation for the Odoo JSON-RPC api.
 */
class Odoo_Form_Bridge extends Form_Bridge
{
    /**
     * Handles the Odoo JSON-RPC well known endpoint.
     *
     * @var string
     */
    private const endpoint = '/jsonrpc';

    /**
     * Handles active RPC session data.
     *
     * @var array Tuple with session and user ids.
     */
    private static $session;

    /**
     * RPC payload decorator.
     *
     * @param int $session_id RPC session ID.
     * @param string $service RPC service name.
     * @param string $method RPC method name.
     * @param array $args RPC request arguments.
     * @param array $more_args RPC additional arguments.
     *
     * @return array JSON-RPC conformant payload.
     */
    public static function rpc_payload(
        $session_id,
        $service,
        $method,
        $args,
        $more_args = []
    ) {
        if (!empty($more_args)) {
            $args[] = $more_args;
        }

        return [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'id' => $session_id,
            'params' => [
                'service' => $service,
                'method' => $method,
                'args' => $args,
            ],
        ];
    }

    /**
     * Handle RPC responses and catch errors on the application layer.
     *
     * @param array $response Request response.
     * @param boolean $is_single Should the result be an entity or an array.
     *
     * @return mixed|WP_Error Request result.
     */
    public static function rpc_response($res)
    {
        if (is_wp_error($res)) {
            return $res;
        }

        if (empty($res['data'])) {
            $content_type =
                Http_Client::get_content_type($res['headers']) ?? 'undefined';

            return new WP_Error(
                'unkown_content_type',
                /* translators: %s: Content-Type header value */
                sprintf(
                    __('Unkown HTTP response content type %s', 'forms-bridge'),
                    sanitize_text_field($content_type)
                ),
                $res
            );
        }

        if (isset($res['data']['error'])) {
            return new WP_Error(
                $res['data']['error']['code'],
                $res['data']['error']['message'],
                $res['data']['error']['data']
            );
        }

        $data = $res['data'];

        if (empty($data['result'])) {
            return new WP_Error(
                'rpc_api_error',
                'An unkown error has ocurred with the RPC API',
                ['response' => $res]
            );
        }

        return $data['result'];
    }

    /**
     * JSON RPC login request.
     *
     * @param Credential $credential
     * @param Http_Backend $backend
     *
     * @return array|WP_Error Tuple with RPC session id and user id.
     */
    private static function rpc_login($credential, $backend)
    {
        if (self::$session) {
            return self::$session;
        }

        $session_id = Forms_Bridge::slug() . '-' . time();

        $payload = self::rpc_payload($session_id, 'common', 'login', [
            $credential->database,
            $credential->user,
            $credential->password,
        ]);

        $response = $backend->post(self::endpoint, $payload);

        $user_id = self::rpc_response($response);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        self::$session = [$session_id, $user_id];
        return self::$session;
    }

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
     * Submits submission to the backend.
     *
     * @param array $payload Submission data.
     * @param array|null $more_args Additional RPC call params.
     *
     * @return array|WP_Error Http
     */
    public function submit($payload = [], $more_args = [])
    {
        $credential = $this->credential();
        $backend = $this->backend();

        if (!$credential) {
            return new WP_Error(
                'invalid_credential',
                'The bridge does not have a valid credential'
            );
        } elseif (!$backend) {
            return new WP_Error(
                'invalid_backend',
                'The bridge does not have a valid backend'
            );
        }

        $session = self::rpc_login($credential, $backend);

        if (is_wp_error($session)) {
            return $session;
        }

        [$sid, $uid] = $session;

        $payload = self::rpc_payload(
            $sid,
            'object',
            'execute',
            [
                $credential->database,
                $uid,
                $credential->password,
                $this->endpoint,
                $this->method,
                $payload,
            ],
            $more_args
        );

        $response = $backend->post(self::endpoint, $payload);

        $result = self::rpc_response($response);
        if (is_wp_error($result)) {
            return $result;
        }

        return $response;
    }
}
