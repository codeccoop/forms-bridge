<?php

namespace FORMS_BRIDGE;

use Exception;
use TypeError;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

class Form_Hook
{
    private $data;
    private $proto;

    /**
     * Form hooks getter.
     *
     * @return array $hooks Array with hooks.
     */
    public static function form_hooks($form_id = null)
    {
        if (empty($form_id)) {
            $form = apply_filters('forms_bridge_form', null, $form_id);
            if (!$form) {
                return [];
            }

            $form_id = $form['id'];
        }

        $rest = Settings::get_setting('forms-bridge', 'rest-api')->form_hooks;
        $rpc = Settings::get_setting('forms-bridge', 'rpc-api')->form_hooks;

        $hooks = [];
        foreach (array_merge($rest, $rpc) as $hook) {
            if ((int) $hook['form_id'] === (int) $form_id) {
                $hooks[$hook['name']] = new Form_Hook($hook);
            }
        }

        return $hooks;
    }

    /**
     * Binds the hook data and sets its protocol.
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->proto = isset($this->data['endpoint']) ? 'rest' : 'rpc';
    }

    /**
     * Magic method to proxy public attributes to method getters.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'proto':
                return $this->proto;
            case 'endpoint':
                return $this->endpoint();
            case 'form':
                return $this->form();
            case 'backend':
                return $this->backend();
            case 'content_type':
                return $this->content_type();
            default:
                return isset($this->data[$name]) ? $this->data[$name] : null;
        }
    }

    /**
     * Retrives the hook's backend instance.
     *
     * @return Http_Backend Backend instance.
     */
    private function backend()
    {
        return apply_filters(
            'http_bridge_backend',
            null,
            $this->data['backend']
        );
    }

    /**
     * Retrives the hook's endpoint.
     *
     * @return string API endpoint.
     */
    private function endpoint()
    {
        if ($this->proto === 'rpc') {
            $endpoint = Settings::get_setting('forms-bridge', 'rpc-api')
                ->endpoint;
        } else {
            $endpoint = $this->data['endpoint'];
        }

        return apply_filters(
            'forms_bridge_endpoint',
            $endpoint,
            $this->data['name'],
            $this
        );
    }

    /**
     * Retrives the hook's form data.
     *
     * @return arrray Form data.
     */
    private function form()
    {
        return apply_filters('forms_bridge_form', null, $this->form_id);
    }

    /**
     * Gets form hook's default body encoding schema.
     *
     * @return string|null Encoding schema.
     */
    private function content_type()
    {
        if ($this->proto === 'rpc') {
            return 'application/json';
        }

        return $this->backend()->content_type();
    }

    /**
     * Submits submission to the backend.
     *
     * @param array $submission Submission data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function submit($submission, $attachments = [])
    {
        if ($this->proto === 'rest') {
            return $this->submit_rest($submission, $attachments);
        } else {
            return $this->submit_rpc($submission);
        }
    }

    /**
     * Submits submission over the REST protocol.
     *
     * @param array $submission Submission data.
     * @param array $attachments Submission attachmeed files.
     *
     * @return array|WP_Error Http request response.
     */
    private function submit_rest($submission, $attachments)
    {
        $backend = $this->backend;
        $method = strtolower($this->method);

        if (!in_array($method, ['get', 'post', 'put', 'delete'])) {
            return new WP_Error(
                'method_not_allowed',
                "HTTP method {$this->method} is not allowed",
                ['method' => $this->method]
            );
        }

        do_action(
            'forms_bridge_before_rest_submit',
            $this->endpoint,
            $submission,
            $attachments,
            $this
        );
        $response = $backend->$method(
            $this->endpoint,
            $submission,
            [],
            $attachments
        );
        do_action(
            'forms_bridge_after_rest_submit',
            $response,
            $this->name,
            $this
        );
        return $response;
    }

    /**
     * Submits submission data over Odoo's JSON-RPC API.
     *
     * @param array $submission Submission payload.
     *
     * @return mixed|WP_Error Request result.
     */
    private function submit_rpc($submission)
    {
        $rpc = Settings::get_setting('forms-bridge', 'rpc-api');
        $backend = $this->backend;

        [$sid, $uid] = $this->rpc_login($backend);
        if (is_wp_error($uid)) {
            return $uid;
        }

        $payload = $this->rpc_payload($sid, 'object', 'execute', [
            $rpc->database,
            $uid,
            $rpc->password,
            $this->model,
            'create',
            $submission,
        ]);

        do_action(
            'forms_bridge_before_rpc_submit',
            $this->endpoint,
            $payload,
            $this
        );
        $response = $backend->post($this->endpoint, $payload);
        do_action(
            'forms_bridge_after_rpc_submit',
            $response,
            $this->name,
            $this
        );

        return $this->rpc_response($response);
    }

    /**
     * JSON RPC login request.
     *
     * @return array Tuple with RPC session id and user id.
     */
    private function rpc_login()
    {
        $session_id = 'forms-bridge-' . time();
        $rpc = Settings::get_setting('forms-bridge', 'rpc-api');
        $backend = $this->backend;

        $payload = $this->rpc_payload($session_id, 'common', 'login', [
            $rpc->database,
            $rpc->user,
            $rpc->password,
        ]);

        do_action(
            'forms_bridge_before_rpc_login',
            $this->endpoint,
            $payload,
            $this
        );
        $response = $backend->post($this->endpoint, $payload);
        do_action(
            'forms_bridge_after_rpc_login',
            $response,
            $this->name,
            $this
        );

        $result = $this->rpc_response($response);
        return [$session_id, $result];
    }

    /**
     * Handle RPC responses and catch errors on the application layer.
     *
     * @param array $response Request response.
     *
     * @return mixed|WP_Error Request result.
     */
    private function rpc_response($response)
    {
        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode($response['body'], true);

        if (isset($data['error'])) {
            return new WP_Error(
                $data['error']['code'],
                $data['error']['message'],
                $data['error']['data']
            );
        }

        return $data['result'];
    }

    /**
     * RPC payload decorator.
     *
     * @param int $session_id RPC session ID.
     * @param string $service RPC service name.
     * @param string $method RPC method name.
     * @param array $args RPC request arguments.
     *
     * @return array JSON-RPC conformant payload.
     */
    private function rpc_payload($session_id, $service, $method, $args)
    {
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
     * Apply cast pipes to data.
     *
     * @param array $data Array of data.
     *
     * @return array Data modified by the hook's pipes.
     */
    public function apply_pipes($data)
    {
        $finger = new JSON_Finger($data);
        foreach ($this->pipes as $pipe) {
            extract($pipe);
            $value = $finger->get($from);
            $finger->unset($from);
            if ($cast !== 'null') {
                $finger->set($to, $this->cast($value, $cast));
            }
        }

        return $finger->data();
    }

    /**
     * Cast value to type.
     *
     * @param mixed $value Original value.
     * @param string $type Target type to cast value.
     *
     * @return mixed $value Casted value.
     */
    private function cast($value, $type)
    {
        switch ($type) {
            case 'string':
                return (string) $value;
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'json':
                try {
                    return json_decode((string) $value, JSON_UNESCAPED_UNICODE);
                } catch (TypeError) {
                    return [];
                }
            case 'null':
                return null;
            default:
                return (string) $value;
        }
    }
}
