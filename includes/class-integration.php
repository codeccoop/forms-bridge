<?php

namespace WPCT_ERP_FORMS;

use WPCT_ABSTRACT\Singleton;
use WPCT_HTTP\Http_Client as Wpct_Http_Client;
use Exception;

abstract class Integration extends Singleton
{
    abstract public function serialize_submission($submission, $form);
    abstract public function serialize_form($form);
    abstract public function get_uploads($submission, $form_data);
    abstract public function init();

    protected function __construct()
    {
        add_action('init', [$this, 'init']);

        add_filter('option_wpct-erp-forms_rest-api', function ($setting) {
            return $this->populate_refs($setting);
        }, 10, 1);
        add_filter('option_wpct-erp-forms_rpc-api', function ($setting) {
            return $this->populate_refs($setting);
        }, 10, 1);
        add_action('update_option', function ($option, $from, $to) {
            if ($option === 'wpct-erp-forms_rest-api' || $option === 'wpct-erp-forms_rpc-api') {
                foreach ($to['forms'] as $form) {
                    $this->set_form_ref($form['form_id'], $form['ref']);
                }
            }
        }, 10, 3);
    }

    public function submit($payload, $endpoints, $uploads, $form_data)
    {
        $success = true;
        foreach ($endpoints as $proto => $urls) {
            foreach ($urls as $url) {
                if (!$success) {
                    continue;
                }

                if ($proto === 'rpc') {
                    $data = apply_filters('wpct_erp_forms_rpc_payload', $this->rpc_payload($url, $payload), $uploads, $form_data);
                } else {
                    $data = $payload;
                }
                if (empty($uploads)) {
                    $response = Wpct_Http_Client::post($url, $data);
                } else {
                    $response = Wpct_Http_Client::post_multipart($url, $data, $uploads);
                }

                $success = $success && !is_wp_error($response);
            }
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

    public function do_submission($submission, $form)
    {
        $form_data = $this->serialize_form($form);
        if ($form_data['ref'] === null) {
            return;
        }

        $uploads = $this->get_uploads($submission, $form_data);
        $uploads = apply_filters('wpct_erp_forms_uploads', array_reduce(array_keys($uploads), function ($carry, $name) use ($uploads) {
            if ($uploads[$name]['is_multi']) {
                for ($i = 1; $i <= count($uploads[$name]['path']); $i++) {
                    $carry[$name . '_' . $i] = $uploads[$name]['path'][$i - 1];
                }
            } else {
                $carry[$name] = $uploads[$name]['path'];
            }

            return $carry;
        }, []), $form_data);

        $data = $this->serialize_submission($submission, $form_data);
        $this->cleanup_empties($data);
        $payload = apply_filters('wpct_erp_forms_payload', $data, $uploads, $form_data);

        $endpoints = apply_filters('wpct_erp_forms_endpoints', $this->get_form_endpoints($form_data['id']), $payload, $uploads, $form_data);

        do_action('wpct_erp_forms_before_submission', $payload, $uploads, $form_data);
        $success = $this->submit($payload, $endpoints, $uploads, $form_data);

        if ($success) {
            do_action('wpct_erp_forms_after_submission', $payload, $uploads, $form_data);
        } else {
            do_action('wpct_erp_forms_on_failure', $payload, $uploads, $form_data);
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

    private function get_form_endpoints($form_id)
    {
        $rest_forms = Settings::get_setting('wpct-erp-forms', 'rest-api', 'forms');
        $rpc_forms = Settings::get_setting('wpct-erp-forms', 'rpc-api', 'forms');
        $rpc_endpoint = Settings::get_setting('wpct-erp-forms', 'rpc-api', 'endpoint');

        $endpoints = [
            'rpc' => $rpc_forms,
            'rest' => $rest_forms,
        ];

        foreach ($endpoints as $proto => $forms) {
            $endpoints[$proto] = array_map(function ($form) use ($rpc_endpoint) {
                return isset($form['endpoint']) ? $form['endpoint'] : $rpc_endpoint;
            }, array_filter(
                $forms,
                function ($form) use ($form_id) {
                    return (string) $form['form_id'] === (string) $form_id;
                }
            ));
        }

        return $endpoints;
    }

    public function get_form_ref($form_id)
    {
        $setting = get_option('wpct-erp-forms_refs', []);
        foreach ($setting as $ref_id => $ref) {
            if ((string) $ref_id === (string) $form_id) {
                return $ref;
            }
        }

        return null;
    }

    public function set_form_ref($form_id, $ref)
    {
        $setting = get_option('wpct-erp-forms_refs', []);
        $setting[$form_id] = $ref;
        update_option('wpct-erp-forms_refs', $setting);
    }

    private function populate_refs($setting)
    {
        $refs = get_option('wpct-erp-forms_refs', []);
        for ($i = 0; $i < count($setting['forms']); $i++) {
            $form = $setting['forms'][$i];
            if (!isset($refs[$form['form_id']])) {
                continue;
            }
            $form['ref'] = $refs[$form['form_id']];
            $setting['forms'][$i] = $form;
        }

        return $setting;
    }

    private function rpc_payload($url, $payload)
    {
        $session_id = time();
        $setting = Settings::get_setting('wpct-erp-forms', 'rpc-api');

		$payload = apply_filters('wpct_erp_forms_rpc_login', [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'id' => $session_id,
            'params' => [
                'service' => 'common',
                'method' => 'login',
                'args' => [
                    $setting['database'],
                    $setting['user'],
                    $setting['password']
                ]
            ]
        ]);
        $res = Wpct_Http_Client::post($url, $payload);

        if (is_wp_error($res)) {
            throw new Exception('Error while establish RPC session');
        }

        $login = (array) json_decode($res['body'], true);
        $user_id = $login['result'];

        return [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'id' => $session_id,
            'params' => [
                'service' => 'object',
                'method' => 'execute',
                'args' => [
                    $setting['database'],
                    $user_id,
                    $setting['password'],
                    $setting['model'],
                    'create',
                    $payload
                ]
            ]
        ];
    }
}
