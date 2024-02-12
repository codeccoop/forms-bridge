<?php

namespace WPCT_ERP_FORMS\Abstract;

use WPCT_HB\Http_Client as Wpct_Http_Client;

abstract class Integration extends Singleton
{
    public static $fields = [];

    abstract public function serialize_submission($submission, $form);
    abstract public function serialize_form($form);

    protected function __construct()
    {
        foreach (static::$fields as $Field) {
            $field = $Field::get_instance();
        }
    }

    public function init()
    {
        foreach (static::$fields as $Field) {
            $field = $Field::get_instance();
            $field->init();
        }
    }

    public function submit($payload, $endpoints, $files = null)
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

                $settings = get_option('wpct_erp_forms_general');
                if (!isset($settings['notification_receiver'])) return;

                $to = $settings['notification_receiver'];
                $subject = 'Wpct ERP Forms Error';
                $body = "Form ID: {$form['id']}\n";
                $body .= "Form title: {$form['title']}";
                $body .= 'Submission: ' . print_r($payload, true);
                wp_mail($to, $subject, $body);
            }
        }

        return $success;
    }

    public function do_submission($submission, $form)
    {
        $form = $this->serialize_form($form);
        if (!$this->has_endpoints($form['id'])) return;

        $files = apply_filters('wpct_erp_forms_submission_files', $this->get_files($submission, $form), $submission, $form);
        $submission = apply_filters('wpct_erp_forms_before_submission', $submission, $form);

        $data = $this->serialize_submission($submission, $form);
        $this->cleanup_empties($data);

        $payload = $this->get_payload($data, $submission, $form);
        $endpoints = $this->get_endpoints($form['id']);

        $success = $this->submit($payload, $endpoints, $files);

        if ($success) do_action('wpct_erp_forms_after_submission', $data, $submission, $form);
        else do_action('wpct_erp_forms_on_failure', $data, $submission, $form, $this);
    }

    public function get_files($submission, $form)
    {
        return [];
    }

    public function get_payload($data, $submission, $form)
    {
        $payload = [
            'name' => $data['source_xml_id'] . ' submission: ' . $data['id'],
            'metadata' => []
        ];

        foreach ($data as $key => $val) {
            if ($key == 'email_from') {
                $payload[$key] = $val;
            } elseif ($key === 'source_xml_id') {
                $payload['source_xml_id'] = $val;
            }

            $payload['metadata'][] = [
                'key' => $key,
                'value' => $val
            ];
        }

        return apply_filters('wpct_erp_forms_payload', $payload, $submission, $form);
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

        return apply_filters('wpct_erp_forms_endpoints', array_map(function ($map) {
            return $map['endpoint'];
        }, $maps));
    }
}
