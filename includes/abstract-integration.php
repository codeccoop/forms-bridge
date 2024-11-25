<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\Singleton;
use WP_Error;
use Exception;
use TypeError;

use function HTTP_BRIDGE\http_bridge_post;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Integration abstract class.
 */
abstract class Integration extends Singleton
{
    /**
     * Retrives the current form.
     *
     * @return array Form data.
     */
    abstract public function get_form();

    /**
     * Retrives form by ID.
     *
     * @return array Form data.
     */
    abstract public function get_form_by_id($form_id);

    /**
     * Retrives available forms.
     *
     * @return array Collection of form data.
     */
    abstract public function get_forms();

    /**
     * Retrives the current form submission.
     *
     * @return array Submission data.
     */
    abstract public function get_submission();

    /**
     * Retrives the current submission uploaded files.
     *
     * @return array Collection of uploaded files.
     */
    abstract public function get_uploads();

    /**
     * Serialize the current form submission data.
     *
     * @param any $submission Pair plugin submission handle.
     * @param array $form_data Source form data.
     * 
     * @return array Submission data.
     */
    abstract public function serialize_submission($submission, $form);

    /**
     * Serialize the current form data.
     *
     * @param any $form Pair plugin form handle.
     * 
     * @return array Form data.
     */
    abstract public function serialize_form($form);

    /**
     * Get uploads from pair submission handle.
     *
     * @param any $submission Pair plugin submission handle.
     * @param array $form_data Current form data.
     * 
     * @return array Collection of uploaded files.
     */
    abstract protected function submission_uploads($submission, $form_data);

    /**
     * Integration initializer to be fired on wp init.
     */
    abstract protected function init();

    /**
     * Binds integration initializer to wp init hook.
     */
    protected function __construct()
    {
        add_action('init', function () {
            $this->init();
        });
    }

    /**
     * Sends error notifications to the email receiver.
     *
     * @param array $form_data Form data.
     * @param array $payload Submission data.
     * @param array $error_data Error data.
     */
    private function notify_error($form_data, $payload, $error = '')
    {
        $email = Settings::get_setting(
            'forms-bridge',
            'general',
            'notification_receiver'
        );
        if (empty($email)) {
            return;
        }

        $to = $email;
        $subject = 'Forms Bridge Error';
        $body = "Form ID: {$form_data['id']}\n";
        $body .= "Form title: {$form_data['title']}\n";
        $body .= 'Submission: ' . print_r($payload, true) . "\n";
        $body .= "Error: {$error}\n";
        $success = wp_mail($to, $subject, $body);
        if (!$success) {
            throw new Exception(
                'Error while submitting form ' . $form_data['id']
            );
        }
    }

    /**
     * Proceed with the submission sub-routine.
     *
     * @param any $submission Pair plugin submission handle.
     * @param any $form Pair plugin form handle.
     */
    public function do_submission($submission, $form)
    {
        $form_data = $this->serialize_form($form);
        if (empty($form_data['hooks'])) {
            return;
        }

        $hooks = (array) $form_data['hooks'];

        $uploads = $this->submission_uploads($submission, $form_data);
        $attachments = apply_filters(
            'forms_bridge_attachments',
            $this->attachments($uploads),
            $form_data
        );

        $payload = apply_filters(
            'forms_bridge_payload',
            $this->serialize_submission($submission, $form_data),
            $attachments,
            $form_data
        );
        $this->cleanup_empties($payload);

        foreach (array_values($hooks) as $hook) {
            $backend = apply_filters(
                'http_bridge_backend',
                null,
                $hook['backend']
            );
            $this->apply_pipes($hook['pipes'], $payload);
            $headers = $backend->get_headers();

            if (isset($hook['method'], $hook['endpoint'])) {
                $url = $backend->get_endpoint_url($hook['endpoint']);
                $args = [
                    'data' => $payload,
                    'files' => $attachments,
                    'headers' => $headers,
                ];

                $this->before_submission(
                    ['url' => $url, 'args' => $args],
                    $form_data
                );
                $res = $this->submit_rest($hook['method'], $url, $args);
            } elseif (isset($hook['model'])) {
                $endpoint = Settings::get_setting(
                    'forms-bridge',
                    'rpc-api',
                    'endpoint'
                );
                $url = $backend->get_endpoint_url($endpoint);

                $res = $this->submit_rpc(
                    $url,
                    $hook['model'],
                    $payload,
                    $attachments,
                    $headers,
                    $form_data
                );
            }

            $this->after_submission($res, $payload, $form_data);
        }
    }

    /**
     * Fired before submission hook.
     *
     * @param array $request Request config.
     * @param array $form_data Form data.
     */
    private function before_submission($request, $form_data)
    {
        do_action('forms_bridge_before_submission', $request, $form_data);
    }

    /**
     * Fired after submission hook.
     *
     * @param array|WP_Error $response Response data.
     * @param array $payload Payload data.
     * @param array $form_data Form data.
     */
    private function after_submission($response, $payload, $form_data)
    {
        if (is_wp_error($response)) {
            do_action('forms_bridge_on_failure', $response, $form_data);

            $this->notify_error(
                $form_data,
                $payload,
                print_r($response->get_error_data(), true)
            );
        } else {
            do_action('forms_bridge_after_submission', $response, $form_data);
        }
    }

    /**
     * Apply cast pipes to the submission data.
     *
     * @param array $payload Submission data.
     * @param array $form_data Form data.
     */
    private function apply_pipes($pipes, &$payload)
    {
        foreach ($payload as $field => $value) {
            foreach ($pipes as $pipe) {
                if ($pipe['from'] === $field) {
                    unset($payload[$field]);
                    if ($pipe['cast'] !== 'null') {
                        $payload[$pipe['to']] = $this->cast(
                            $pipe['cast'],
                            $value
                        );
                    }
                }
            }
        }
    }

    /**
     * Cast value to type.
     *
     * @param string $type Target type to cast value.
     * @param mixed $value Original value.
     * 
     * @return mixed $value Casted value.
     */
    private function cast($type, $value)
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

    /**
     * Clean up submission empty fields.
     *
     * @param array $submission_data Submission data.
     * 
     * @return array Submission data without empty fields.
     */
    private function cleanup_empties(&$submission_data)
    {
        foreach ($submission_data as $key => $val) {
            if ($val === '' || $val === null) {
                unset($submission_data[$key]);
            }
        }
    }

    /**
     * Transform collection of uploads to an attachments map.
     *
     * @param array $uploads Collection of uploaded files.
     * 
     * @return array Map of uploaded files.
     */
    private function attachments($uploads)
    {
        return array_reduce(
            array_keys($uploads),
            function ($carry, $name) use ($uploads) {
                if ($uploads[$name]['is_multi']) {
                    for ($i = 1; $i <= count($uploads[$name]['path']); $i++) {
                        $carry[$name . '_' . $i] =
                            $uploads[$name]['path'][$i - 1];
                    }
                } else {
                    $carry[$name] = $uploads[$name]['path'];
                }

                return $carry;
            },
            []
        );
    }

    /**
     * Submit REST requests over HTTP methods.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE).
     * @param string $url Target URL.
     * @param array $args Request arguments.
     * 
     * @return array|WP_Error Request response.
     */
    private function submit_rest($method, $url, $args)
    {
        $m = strtolower($method);
        $func = "\HTTP_BRIDGE\http_bridge_{$m}";
        if (!is_callable($func)) {
            return new WP_Error(
                'http_method_not_allowed',
                __("Unkown HTTP method: {$method}", 'forms-bridge'),
                ['status' => 405]
            );
        }

        if (in_array($method, ['GET', 'DELETE'])) {
            unset($args['headers']['Content-Type']);
            $args['params'] = $args['data'];
        }

        return $func($url, $args);
    }

    /**
     * JSON RPC login request.
     *
     * @param string $endpoint Target endpoint.
     * 
     * @return array Tuple with RPC session id and user id.
     */
    private function rpc_login($url)
    {
        $session_id = 'forms-bridge-' . time();
        [
            'database' => $database,
            'user' => $user,
            'password' => $password,
        ] = Settings::get_setting('forms-bridge', 'rpc-api');

        $payload = apply_filters(
            'forms_bridge_rpc_login',
            $this->rpc_payload($session_id, 'common', 'login', [
                $database,
                $user,
                $password,
            ])
        );

        $response = http_bridge_post($url, ['data' => $payload]);

        if (is_wp_error($response)) {
            return $response;
        }

        $login = (array) json_decode($response['body'], true);
        if (isset($login['error'])) {
            return new WP_Error(
                $login['error']['code'],
                $login['error']['message'],
                $login['error']['data']
            );
        }

        return [$login['id'], $login['result']];
    }

    /**
     * Submit submission over Odoo's JSON-RPC API.
     *
     * @param array $models Array of target models.
     * @param array $payload Submission data.
     * @param array $attachments Collection of attachment files.
     * @param array $form_data Source form data.
     * 
     * @return array|WP_Error HTTP response or error.
     */
    private function submit_rpc(
        $url,
        $model,
        $payload,
        $attachments,
        $headers,
        $form_data
    ) {
        $this->before_submission(
            [
                'url' => $url,
                'args' => [
                    'data' => $payload,
                    'headers' => $headers,
                    'files' => $attachments,
                ],
            ],
            $form_data
        );

        $database = Settings::get_setting(
            'forms-bridge',
            'rpc-api',
            'database'
        );
        $password = Settings::get_setting(
            'forms-bridge',
            'rpc-api',
            'password'
        );

        $login = $this->rpc_login($url);
        if (is_wp_error($login)) {
            return $login;
        }

        [$session_id, $user_id] = $login;

        $payload = apply_filters(
            'forms_bridge_rpc_payload',
            $this->rpc_payload($session_id, 'object', 'execute', [
                $database,
                $user_id,
                $password,
                $model,
                'create',
                $payload,
            ]),
            $attachments,
            $form_data
        );

        $response = http_bridge_post($url, [
            'data' => $payload,
            'files' => $attachments,
            'headers' => $headers,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = (array) json_decode($response['body'], true);

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
}
