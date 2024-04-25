<?php

namespace WPCT_ERP_FORMS\Abstract;

use WPCT_HTTP\Http_Client as Wpct_Http_Client;
use Exception;

abstract class Integration extends Singleton
{
    public static $fields = [];

    abstract public function serialize_submission($submission, $form);
    abstract public function serialize_form($form);

    protected function __construct()
    {
        foreach (static::$fields as $Field) {
            $field = $Field::get_instance();
            add_action('init', function () use ($field) {
                $field->init();
            });
        }

        add_action('init', [$this, 'init']);
    }

    public function init()
    {
    }

    public function submit($payload, $endpoints, $files, $form_data)
    {
        $success = true;
        foreach ($endpoints as $endpoint) {
            if (empty($files)) {
                $response = Wpct_Http_Client::post($endpoint, $payload);
            } else {
                $response = Wpct_Http_Client::post_multipart($endpoint, $payload, $files);
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

        $data = $this->serialize_submission($submission, $form_data);
        $this->cleanup_empties($data);

        $payload = apply_filters('wpct_erp_forms_payload', $data, $uploads, $form_data);
        $files = apply_filters('wpct_erp_forms_submission_files', array_reduce(array_keys($uploads), function ($carry, $name) use ($uploads) {
            $paths = $uploads[$name]['is_multi'] ? $uploads[$name]['path'] : [$uploads[$name]['path']];
            return array_merge($carry, $paths);
        }, []), $uploads, $form_data);
        $endpoints = apply_filters('wpct_erp_forms_endpoints', $this->get_endpoints($form_data['id']), $payload, $files, $form_data);

        do_action('wpct_erp_forms_before_submission', $payload, $files, $form_data);
        $success = $this->submit($payload, $endpoints, $files, $form_data);

        if ($success) {
            do_action('wpct_erp_forms_after_submission', $payload, $files, $form_data);
        } else {
            do_action('wpct_erp_forms_on_failure', $payload, $files, $form_data);
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
}
