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
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'odoo';

    /**
     * Handles the Odoo JSON-RPC well known endpoint.
     *
     * @var string
     */
    private const endpoint = '/jsonrpc';

    /**
     * Handles the array of accepted HTTP header names of the bridge API.
     *
     * @var array<string>
     */
    protected static $api_headers = ['Content-Type', 'Accept'];

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
        $more_args = null
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
     * @param array $credential Credential data.
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
            $credential['database'],
            $credential['user'],
            $credential['password'],
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
     * Bridge's credential data getter.
     *
     * @return array|null
     */
    protected function credential()
    {
        $credentials = Forms_Bridge::setting($this->api)->credentials ?: [];
        foreach ($credentials as $credential) {
            if ($credential['name'] === $this->data['credential']) {
                return $credential;
            }
        }
    }

    /**
     * Submits submission to the backend.
     *
     * @param array $payload Submission data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    protected function do_submit($payload, $more_args = null)
    {
        $credential = $this->credential();

        $session = self::rpc_login($credential, $this->backend);

        if (is_wp_error($session)) {
            return $session;
        }

        [$sid, $uid] = $session;

        $payload = self::rpc_payload(
            $sid,
            'object',
            'execute',
            [
                $credential['database'],
                $uid,
                $credential['password'],
                $this->endpoint,
                $this->method ?? 'create',
                $payload,
            ],
            $more_args
        );

        $response = $this->backend()->post(self::endpoint, $payload);

        $result = self::rpc_response($response);
        if (is_wp_error($result)) {
            return $result;
        }

        return $response;
    }

    protected function api_schema()
    {
        $response = $this->patch([
            'name' => 'odoo-api-schema-introspection',
            'method' => 'fields_get',
        ])->submit([]);

        if (is_wp_error($response)) {
            return [];
        }

        $fields = [];
        foreach ($response['data']['result'] as $name => $spec) {
            if ($spec['readonly']) {
                continue;
            }

            if ($spec['type'] === 'char') {
                $type = 'string';
            } elseif ($spec['type'] === 'html') {
                $type = 'string';
            } elseif ($spec['type'] === 'float') {
                $type = 'number';
            } else {
                $type = $spec['type'];
            }

            $fields[] = [
                'name' => $name,
                'schema' => [
                    'type' => $type,
                    'required' => $spec['required'],
                ],
            ];
        }

        return $fields;
    }
}
