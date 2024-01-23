<?php

namespace WPCT_ERP_FORMS\Integrations;

use Exception;

class Integration
{
    public static $fields = [];
    private static $instances = [];

    public function __construct()
    {
        add_action('init', [$this, 'register']);

        foreach (static::$fields as $field) {
            $_field = $field::get_instance();
            $_field->register();
        }
    }

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function get_instance()
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::class::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }

    public function submit($payload, $endpoints)
    {
        $success = true;
        foreach ($endpoints as $endpoint) {
            $response = wpct_oc_post_odoo($endpoint, $payload);

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

        $submission = $this->serialize_submission($submission, $form);

        $submission = apply_filters('wpct_erp_forms_before_submission', $submission, $form);
        $this->cleanup_empties($submission);

        $payload = $this->get_payload($submission);
        $endpoints = $this->get_endpoints($form['id']);

        $success = $this->submit($payload, $endpoints);

        if ($success) do_action('wpct_erp_forms_after_submission', $submission, $form);
        else do_action('wpct_erp_forms_on_failure', $submission, $form);
    }

    public function get_payload($submission)
    {
        $payload = [
            'name' => $submission['source_xml_id'] . ' submission: ' . $submission['id'],
            'metadata' => []
        ];

        foreach ($submission as $key => $val) {
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

        return apply_filters('wpct_erp_forms_payload', $payload);
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
            get_option('wpct_erp_forms_api', ['endpoints' => []])['endpoints'],
            function ($map) use ($form_id) {
                return (string) $map['form_id'] === (string) $form_id;
            }
        );

        return apply_filters('wpct_erp_forms_endpoints', array_map(function ($map) {
            return $map['endpoint'];
        }, $maps));
    }

    public function register()
    {
        throw new Exception('Method to overwrite by inheritance');
    }

    public function serialize_submission($submission, $form)
    {
        throw new Exception('Method to overwrite by inheritance');
    }

    public function serialize_form($form)
    {
        throw new Exception('Method to overwrite by inheritance');
    }
}
