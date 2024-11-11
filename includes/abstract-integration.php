<?php

namespace WPCT_ERP_FORMS;

use WPCT_ABSTRACT\Singleton;
use WP_Error;
use Exception;
use TypeError;

/**
 * Integration abstract class.
 *
 * @since 1.0.0
 */
abstract class Integration extends Singleton
{
    /**
     * Retrive the current form.
     *
     * @return array $form_data Form data.
     */
    abstract public function get_form();

    /**
     * Retrive form by ID.
     *
     * @since 3.0.0
     *
     * @return array $form_data Form data.
     */
    abstract public function get_form_by_id($form_id);

    /**
     * Retrive available forms.
     *
     * @since 3.0.0
     *
     * @return array $forms Collection of form data.
     */
    abstract public function get_forms();

    /**
     * Retrive the current form submission.
     *
     * @since 3.0.0
     *
     * @return array $submission Submission data.
     */
    abstract public function get_submission();

    /**
     * Retrive the current submission uploaded files.
     *
     * @since 3.0.0
     *
     * @return array $files Collection of uploaded files.
     */
    abstract public function get_uploads();

    /**
     * Serialize the current form submission data.
     *
     * @since 1.0.0
     *
     * @param any $submission Pair plugin submission handle.
     * @param array $form_data Source form data.
     * @return array $submission_data Submission data.
     */
    abstract public function serialize_submission($submission, $form);

    /**
     * Serialize the current form data.
     *
     * @since 1.0.0
     *
     * @param any $form Pair plugin form handle.
     * @return array $form_data Form data.
     */
    abstract public function serialize_form($form);

    /**
     * Get uploads from pair submission handle.
     *
     * @since 1.0.0
     *
     * @param any $submission Pair plugin submission handle.
     * @param array $form_data Current form data.
     * @return array $uploads Collection of uploaded files.
     */
    abstract protected function submission_uploads($submission, $form_data);

    /**
     * Integration initializer to be fired on wp init.
     *
     * @since 0.0.1
     */
    abstract protected function init();

    /**
     * Bind integration initializer to wp init hook.
     *
     * @since 0.0.1
     */
    protected function __construct()
    {
        add_action('init', function () {
            $this->init();
        });
    }

    /**
     * Send error notifications to the email receiver.
     *
     * @since 3.0.0
     *
     * @param array $form_data Form data.
     * @param array $payload Submission data.
     * @param array $error_data Error data.
     */
    private function notify_error($form_data, $payload, $error = '')
    {
        $email = Settings::get_setting(
            'wpct-erp-forms',
            'general',
            'notification_receiver'
        );
        if (empty($email)) {
            return;
        }

        $to = $email;
        $subject = 'Wpct ERP Forms Error';
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
     * @since 1.0.0
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
            'wpct_erp_forms_attachments',
            $this->attachments($uploads),
            $form_data
        );

        $payload = apply_filters(
            'wpct_erp_forms_payload',
            $this->serialize_submission($submission, $form_data),
            $attachments,
            $form_data
        );
        $this->cleanup_empties($payload);

        do_action(
            'wpct_erp_forms_before_submission',
            $payload,
            $attachments,
            $form_data
        );

        $error = false;
        foreach (array_values($hooks) as $hook) {
            if ($error) {
                break;
            }

            $backend = apply_filters(
                'wpct_http_backend',
                null,
                $hook['backend']
            );
            $this->apply_pipes($hook['pipes'], $payload);
            $headers = $backend->get_headers();
            if (isset($hook['method'], $hook['endpoint'])) {
                $url = $backend->get_endpoint_url($hook['endpoint']);
                $res = $this->submit_rest($hook['method'], $url, [
                    'data' => $payload,
                    'files' => $attachments,
                    'headers' => $headers,
                ]);
            } elseif (isset($hook['model'])) {
                $endpoint = Settings::get_setting(
                    'wpct-erp-forms',
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

            if (is_wp_error($res)) {
                $this->notify_error(
                    $form_data,
                    $payload,
                    print_r($res->get_error_data(), true)
                );
                $error = true;
            }
        }

        if (!$error) {
            do_action(
                'wpct_erp_forms_after_submission',
                $payload,
                $attachments,
                $form_data
            );
        } else {
            do_action(
                'wpct_erp_forms_on_failure',
                $payload,
                $attachments,
                $form_data
            );
        }
    }

    /**
     * Apply cast pipes to the submission data.
     *
     * @since 3.0.0
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
     * @since 3.0.0
     *
     * @param string $type Target type to cast value.
     * @param any $value Original value.
     * @return any $value Casted value.
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
     * @since 1.0.0
     *
     * @param array $submission_data Submission data.
     * @return array $submission_data Submission data without empty fields.
     */
    private function cleanup_empties(&$submission_data)
    {
        foreach ($submission_data as $key => $val) {
            if (empty($val)) {
                unset($submission_data[$key]);
            }
        }
    }

    /**
     * Transform collection of uploads to an attachments map.
     *
     * @since 3.0.0
     *
     * @param array $uploads Collection of uploaded files.
     * @return array $uploads Map of uploaded files.
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
     * @since 3.0.4
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE).
     * @param string $url Target URL.
     * @param array $args Request arguments.
     * @return array|WP_Error Request response.
     */
    private function submit_rest($method, $url, $args)
    {
        $m = strtolower($method);
        $func = "\WPCT_HTTP\wpct_http_{$m}";
        if (!is_callable($func)) {
            return new WP_Error(
                'http_method_not_allowed',
                __("Unkown HTTP method: {$method}", 'wpct-erp-forms'),
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
     * @since 2.0.0
     *
     * @param string $endpoint Target endpoint.
     * @return array $credentials Tuple with $session_id and $user_id.
     */
    private function rpc_login($url)
    {
        $session_id = time();
        [
            'database' => $database,
            'user' => $user,
            'password' => $password,
        ] = Settings::get_setting('wpct-erp-forms', 'rpc-api');

        $payload = apply_filters(
            'wpct_erp_forms_rpc_login',
            $this->rpc_payload($session_id, 'common', 'login', [
                $database,
                $user,
                $password,
            ])
        );

        $res = wpct_http_post($url, ['data' => $payload]);

        if (is_wp_error($res)) {
            throw new Exception('Error while establish RPC session');
        }

        $login = (array) json_decode($res['body'], true);
        if (isset($login['error'])) {
            throw new Exception('RPC login error');
        }

        $user_id = isset($login['result']) ? $login['result'] : null;
        return [$session_id, $user_id];
    }

    /**
     * Submit submission over Odoo's JSON-RPC API.
     *
     * @since 2.0.0
     *
     * @param array $models Array of target models.
     * @param array $payload Submission data.
     * @param array $attachments Collection of attachment files.
     * @param array $form_data Source form data.
     * @return array|WP_Error $response HTTP response.
     */
    private function submit_rpc(
        $url,
        $model,
        $payload,
        $attachments,
        $headers,
        $form_data
    ) {
        $database = Settings::get_setting(
            'wpct-erp-forms',
            'rpc-api',
            'database'
        );
        $password = Settings::get_setting(
            'wpct-erp-forms',
            'rpc-api',
            'password'
        );

        try {
            [$session_id, $user_id] = $this->rpc_login($url);
        } catch (Exception) {
            return false;
        }

        $payload = apply_filters(
            'wpct_erp_forms_rpc_payload',
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

        $response = wpct_http_post($url, [
            'data' => $payload,
            'files' => $attachments,
            'headers' => $headers,
        ]);
        if (isset($response['error'])) {
            return new WP_Error(
                'rpc_api_error',
                'RPC API error response',
                $response['error']
            );
        }

        return $response;
    }

    /**
     * RPC payload decorator.
     *
     * @since 2.0.0
     *
     * @param int $session_id RPC session ID.
     * @param string $service RPC service name.
     * @param string $method RPC method name.
     * @param array $args RPC request arguments.
     * @return array $payload RPC conformant payload.
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
