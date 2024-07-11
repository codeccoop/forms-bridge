<?php

namespace WPCT_ERP_FORMS;

use WPCT_ABSTRACT\Singleton;
use WPCT_HTTP\Http_Client as Wpct_Http_Client;
use Exception;

abstract class Integration extends Singleton
{
    abstract public function serialize_submission($submission, $form);
    abstract public function serialize_form($form);
    abstract protected function get_uploads($submission, $form_data);
    abstract protected function init();

    private $submission = null;
    private $uploads = null;

    protected function __construct()
    {
        add_action('init', function () {
            $this->init();
        });

        add_filter('wpct_erp_forms_submission', function ($null) {
            return $this->submission;
        });

        add_filter('wpct_erp_forms_uploads', function ($null) {
            return $this->uploads;
        });
    }

    private function submit($requests)
    {
        $success = true;
        foreach ($requests as $req) {
            if (!$success) {
                continue;
            }

            extract($req);
            if (empty($attachments)) {
                $response = Wpct_Http_Client::post($endpoint, $payload);
            } else {
                $response = Wpct_Http_Client::post_multipart($endpoint, $payload, $attachments);
            }

            $success = $success && !is_wp_error($response) && apply_filters('_wpct_erp_forms_validate_rpc_response', true, $response);
        }

        if (!$success) {
            $email = Settings::get_setting('wpct-erp-forms', 'general', 'notification_receiver');
            if (empty($email)) {
                return;
            }

            $to = $email;
            $subject = 'Wpct ERP Forms Error';
            $body = "Form ID: {$form_data['id']}\n";
            $body .= "Form title: {$form_data['title']}\n";
            $body .= 'Submission: ' . print_r($payload, true) . "\n";
            $body .= 'Error: ' . print_r($response->get_error_data(), true) . "\n";
            $success = wp_mail($to, $subject, $body);
            if (!$success) {
                throw new Exception('Error while submitting form ' . $form_data['id']);
            }
        }

        return $success;
    }

    private function submit_rpc($models, $payload, $attachments, $form_data)
    {
        $setting = Settings::get_setting('wpct-erp-forms', 'rpc-api');

        try {
            [ $session_id, $user_id ] = $this->rpc_login($setting['endpoint']);
        } catch (Exception) {
            return false;
        }

        $requests = array_map(function ($model) use (
            $setting,
            $payload,
            $attachments,
            $form_data,
            $session_id,
            $user_id,
        ) {
            $payload = apply_filters(
                'wpct_erp_forms_rpc_payload',
                $this->rpc_payload(
                    $session_id,
                    'object',
                    'execute',
                    [
                        $setting['database'],
                        $user_id,
                        $setting['password'],
                        $model,
                        'create',
                        $payload
                    ],
                ),
                $attachments,
                $form_data,
            );

            return [
                'endpoint' => $setting['endpoint'],
                'payload' => $payload,
                'attachments' => $attachments,
                'form_data' => $form_data,
            ];
        }, $models);

        $validation = fn ($result, $res) => $this->rpc_response_validation($res);
        add_filter('_wpct_erp_forms_validate_rpc_response', $validation, 2, 10);

        $result = $this->submit($requests);

        remove_filter('_wpct_erp_forms_validate_rpc_response', $validation, 2, 10);
        return $result;
    }

    private function submit_rest($endpoints, $payload, $attachments, $form_data)
    {
        $requests = array_map(function ($endpoint) use ($payload, $attachments, $form_data) {
            return [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'attachments' => $attachments,
                'form_data' => $form_data,
            ];
        }, $endpoints);

        return $this->submit($requests);
    }

    public function do_submission($submission, $form)
    {
        $form_data = $this->serialize_form($form);
        if ($form_data['ref'] === null) {
            return;
        }

        $this->uploads = $this->get_uploads($submission, $form_data);
        $attachments = apply_filters('wpct_erp_forms_attachments', array_reduce(array_keys($this->uploads), function ($carry, $name) {
            if ($this->uploads[$name]['is_multi']) {
                for ($i = 1; $i <= count($this->uploads[$name]['path']); $i++) {
                    $carry[$name . '_' . $i] = $this->uploads[$name]['path'][$i - 1];
                }
            } else {
                $carry[$name] = $this->uploads[$name]['path'];
            }

            return $carry;
        }, []), $form_data);

        $this->submission = $this->serialize_submission($submission, $form_data);
        $this->cleanup_empties($submission);
        $payload = apply_filters('wpct_erp_forms_payload', $this->submission, $attachments, $form_data);

        $endpoints = apply_filters('wpct_erp_forms_endpoints', $this->get_form_endpoints($form_data['id']), $payload, $attachments, $form_data);
        $models = apply_filters('wpct_erp_forms_models', $this->get_form_models($form_data['id']), $payload, $attachments, $form_data);

        do_action('wpct_erp_forms_before_submission', $payload, $attachments, $form_data);

        $success = $this->submit_rest($endpoints, $payload, $attachments, $form_data);
        $success = $success && $this->submit_rpc($models, $payload, $attachments, $form_data);

        if ($success) {
            do_action('wpct_erp_forms_after_submission', $payload, $attachments, $form_data);
        } else {
            do_action('wpct_erp_forms_on_failure', $payload, $attachments, $form_data);
        }
    }

    private function cleanup_empties(&$submission)
    {
        foreach ($submission as $key => $val) {
            if (empty($val)) {
                unset($submission[$key]);
            }
        }

        return $submission;
    }

    private function get_form_models($form_id)
    {
        $rpc_forms = Settings::get_setting('wpct-erp-forms', 'rpc-api', 'forms');
        return array_unique(array_map(function ($form) {
            return $form['model'];
        }, array_filter($rpc_forms, function ($form) use ($form_id) {
            return (string) $form['form_id'] === (string) $form_id && !empty($form['model']);
        })));
    }

    private function get_form_endpoints($form_id)
    {
        $rest_forms = Settings::get_setting('wpct-erp-forms', 'rest-api', 'forms');
        return array_unique(array_map(function ($form) {
            return $form['endpoint'];
        }, array_filter($rest_forms, function ($form) use ($form_id) {
            return (string) $form['form_id'] === (string) $form_id && !empty($form['endpoint']);
        })));
    }

    private function rpc_login($endpoint)
    {
        $session_id = time();
        $setting = Settings::get_setting('wpct-erp-forms', 'rpc-api');

        $payload = apply_filters('wpct_erp_forms_rpc_login', $this->rpc_payload(
            $session_id,
            'common',
            'login',
            [
                $setting['database'],
                $setting['user'],
                $setting['password'],
            ],
        ));

        $res = Wpct_Http_Client::post($endpoint, $payload);

        if (is_wp_error($res)) {
            throw new Exception('Error while establish RPC session');
        }

        $login = (array) json_decode($res['body'], true);
        if (isset($login['error'])) {
            throw new Exception('RPC login error');
        }
        $user_id = $login['result'];
        return [$session_id, $user_id];
    }

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

    private function rpc_response_validation($res)
    {
        $payload = (array) json_decode($res['body'], true);
        if (isset($payload['error'])) {
            return false;
        }

        return true;
    }
}
