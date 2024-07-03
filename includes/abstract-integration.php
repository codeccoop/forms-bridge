<?php

namespace WPCT_ABSTRACT;

use WPCT_HTTP\Http_Client as Wpct_Http_Client;
use Exception;

abstract class Integration extends WPCT_ABSTRACT\Singleton
{
    abstract public function serialize_submission($submission, $form);
    abstract public function serialize_form($form);

    protected function __construct()
    {
        add_action('init', [$this, 'init']);
    }

    public function init()
    {
    }

    public function submit($payload, $endpoints, $uploads, $form_data)
    {
        $success = true;
        foreach ($endpoints as $endpoint) {

            if (empty($uploads)) {
                $response = Wpct_Http_Client::post($endpoint, $payload);
            } else {
                $response = Wpct_Http_Client::post_multipart($endpoint, $payload, $uploads);
            }

            if (!$response) {
                $success = false;

                $settings = get_option('wpct-erp-forms_general');
                if (!isset($settings['notification_receiver'])) {
                    return;
                }

                $to = $settings['notification_receiver'];
                $subject = 'Wpct ERP Forms Error';
                $body = "Form ID: {$form_data['id']}\n";
                $body .= "Form title: {$form_data['title']}";
                $body .= 'Submission: ' . print_r($payload, true);
                $success = wp_mail($to, $subject, $body);
                if (!$success) {
                    throw new Exception('Error while submitting form ' . $form_data['id']);
                }
            }
        }

        return $success;
    }

    public function do_submission($submission, $form)
    {
        $form_data = $this->serialize_form($form);
        if (!$this->has_endpoints($form_data['id'])) {
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

        $endpoints = apply_filters('wpct_erp_forms_endpoints', $this->get_endpoints($form_data['id']), $payload, $uploads, $form_data);

        do_action('wpct_erp_forms_before_submission', $payload, $uploads, $form_data);
        $success = $this->submit($payload, $endpoints, $uploads, $form_data);

        if ($success) {
            do_action('wpct_erp_forms_after_submission', $payload, $uploads, $form_data);
        } else {
            do_action('wpct_erp_forms_on_failure', $payload, $uploads, $form_data);
        }
    }

    public function get_uploads($submission, $form_data)
    {
        return [];
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

    public function has_endpoints($form_id)
    {
        return sizeof($this->get_endpoints($form_id)) > 0;
    }

    private function get_endpoints($form_id)
    {
        $maps = array_filter(
            get_option('wpct-erp-forms_api', ['endpoints' => []])['endpoints'],
            function ($map) use ($form_id) {
                return (string) $map['form_id'] === (string) $form_id;
            }
        );

        return array_map(function ($map) {
            return $map['endpoint'];
        }, $maps);
    }

    public function get_form_ref($form_id)
    {
        $setting = get_option('wpct-erp-forms_api', ['endpoints' => []]);
        foreach ($setting['endpoints'] as $endpoint) {
            if ((string) $endpoint['form_id'] === (string) $form_id) {
                return isset($endpoint['ref']) ? $endpoint['ref'] : null;
            }
        }

        return null;
    }

    public function set_form_ref($form_id, $ref)
    {
        $setting = get_option('wpct-erp-forms_api', ['endpoints' => []]);
        for ($i = 0; $i < count($setting['endpoints']); $i++) {
            if ((string) $setting['endpoints'][$i]['form_id'] === (string) $form_id) {
                $endpoints[$i]['ref'] = $ref;
                break;
            }
        }
    }
}
