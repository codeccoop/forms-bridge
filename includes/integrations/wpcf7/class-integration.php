<?php

namespace WPCT_ERP_FORMS\WPCF7;

use WPCT_ERP_FORMS\Integration as BaseIntegration;

class Integration extends BaseIntegration
{
    protected function __construct()
    {
        parent::__construct();

        add_filter('wpcf7_before_send_mail', function ($form, &$abort, $submission) {
            $this->do_submission($submission, $form);
        }, 10, 3);
    }

    public function init()
    {
    }

    public function serialize_field($field, $form_data)
    {
        $type = $field->basetype;
        if ($type === 'conditional') {
            $type = $field->get_option('type')[0];
        }

        $options = [];
        if (is_array($field->values)) {
            $values = $field->pipes->collect_afters();
            for ($i = 0; $i < sizeof($field->raw_values); $i++) {
                $options[] = [
                    'value' => $values[$i],
                    'label' => $field->labels[$i],
                ];
            }
        }

        return [
            'id' => $field->get_id_option(),
            'type' => $type,
            'name' => $field->raw_name,
            'label' => $field->name,
            'required' => $field->is_required(),
            'options' => $options,
            'conditional' => $field->basetype === 'conditional' || $field->basetype === 'fileconditional',
        ];
    }

    public function serialize_submission($submission, $form_data)
    {
        $data = $submission->get_posted_data();
        $data['submission_id'] = $submission->get_posted_data_hash();
        foreach ($data as $key => $val) {
            $i = array_search($key, array_column($form_data['fields'], 'name'));
            $field = $form_data['fields'][$i];

            if ($field['type'] === 'hidden') {
                $number_val = (float) $val;
                if ((string) $number_val === $val) {
                    $data[$key] = $number_val;
                }
            } elseif ($field['type'] === 'number') {
                $data[$key] = (float) $val;
            } elseif ($field['type'] === 'file' || $field['type'] === 'submit') {
                unset($data[$key]);
            }
        }

        return $data;
    }

    public function serialize_form($form)
    {
        return [
            'id' => $form->id(),
            'title' => $form->title(),
            'ref' => $this->get_form_ref($form->id()),
            'fields' => array_map(function ($field) use ($form) {
                return $this->serialize_field($field, $form);
            }, $form->scan_form_tags()),
        ];
    }

    public function get_uploads($submission, $form_data)
    {
        $uploads = [];
        $uploads = $submission->uploaded_files();
        foreach ($uploads as $file_name => $paths) {
            if (!empty($paths)) {
                $is_multi = sizeof($paths) > 1;
                $uploads[$file_name] = [
                    'path' => $is_multi ? $paths : $paths[0],
                    'is_multi' => $is_multi,
                ];
            }
        };

        return $uploads;
    }
}
