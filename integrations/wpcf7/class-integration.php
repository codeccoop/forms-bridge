<?php

namespace FORMS_BRIDGE\WPCF7;

use FORMS_BRIDGE\Integration as BaseIntegration;
use WPCF7_ContactForm;
use WPCF7_Submission;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * ContactForm7 integration.
 */
class Integration extends BaseIntegration
{
    /**
     * Inherit parent constructor and hooks submissions to wpcf7_before_send_mail
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        add_filter(
            'wpcf7_submit',
            function ($form, $result) {
                if (
                    in_array(
                        $result['status'],
                        ['validation_failed', 'acceptance_missing', 'spam'],
                        true
                    )
                ) {
                    return;
                }
                $this->do_submission();
            },
            10,
            2
        );
    }

    /**
     * Integration initializer to be fired on wp init.
     */
    protected function init()
    {
    }

    /**
     * Retrive the current form data.
     *
     * @return array $form_data Form data array representation.
     */
    public function form()
    {
        $form = WPCF7_ContactForm::get_current();
        if (!$form) {
            return null;
        }

        return $this->serialize_form($form);
    }

    /**
     * Retrive form data by ID.
     *
     * @param int $form_id Form ID.
     * @return array $form_data Form data.
     */
    public function get_form_by_id($form_id)
    {
        $form = WPCF7_ContactForm::get_instance($form_id);
        if (!$form) {
            return null;
        }

        return $this->serialize_form($form);
    }

    /**
     * Retrive available integration's forms data.
     *
     * @return array $forms Collection of form data.
     */
    public function forms()
    {
        $forms = WPCF7_ContactForm::find(['post_status', 'publish']);
        return array_map(function ($form) {
            return $this->serialize_form($form);
        }, $forms);
    }

    /**
     * Retrive the current submission data.
     *
     * @return array Submission data.
     */
    public function submission()
    {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return null;
        }

        return $this->serialize_submission($submission, $this->form());
    }

    /**
     * Retrive the current submission uploaded files.
     *
     * @return array Uploaded files data.
     */
    public function uploads()
    {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return null;
        }

        return $this->submission_uploads($submission, $this->form());
    }

    /**
     * Serialize form data.
     *
     * @param WPCF7_ContactForm $form Form instance.
     *
     * @return array Form data.
     */
    public function serialize_form($form)
    {
        $form_id = (int) $form->id();
        return [
            '_id' => 'wpcf7:' . $form_id,
            'id' => $form_id,
            'title' => $form->title(),
            'hooks' => apply_filters(
                'forms_bridge_form_hooks',
                [],
                'wpcf7:' . $form_id
            ),
            'fields' => array_values(
                array_filter(
                    array_map(function ($field) {
                        return $this->serialize_field($field);
                    }, $form->scan_form_tags())
                )
            ),
        ];
    }

    /**
     * Serialize form tags to array.
     *
     * @param WPCF7_FormTag $field Form tag instance.
     * @param array $form_data Form data.
     *
     * @return array Field data.
     */
    private function serialize_field($field)
    {
        if (in_array($field->basetype, ['response', 'submit'])) {
            return;
        }

        $type = $field->basetype;
        if ($type === 'conditional') {
            $type = $field->get_option('type')[0];
        }

        $type = $this->norm_field_type($type);

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
            'is_file' => $type === 'file',
            'is_multi' =>
                ($field->basetype === 'checkbox' &&
                    !$field->has_option('exclusive')) ||
                ($field->basetype === 'select' &&
                    $field->has_option('multiple')),
            'conditional' =>
                $field->basetype === 'conditional' ||
                $field->basetype === 'fileconditional',
        ];
    }

    private function norm_field_type($type)
    {
        switch ($type) {
            case 'iban':
            case 'vat':
            case 'email':
            case 'url':
            case 'textarea':
            case 'quiz':
                return 'text';
            case 'select':
            case 'checkbox':
            case 'radio':
                return 'options';
            case 'files':
                return 'file';
            case 'acceptance':
                return 'consent';
            default:
                return $type;
        }
    }

    /**
     * Serialize the form's submission data.
     *
     * @param WPCF7_Submission $submission Submission instance.
     * @param array $form Form data.
     *
     * @return array Submission data.
     */
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
            } elseif (is_array($val) && !$field['is_multi']) {
                $data[$key] = $val[0];
            } elseif ($field['type'] === 'file') {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Gets submission uploaded files.
     *
     * @param WPCF7_Submission $submission Submission instance.
     * @param array $form_data Form data.
     *
     * @return array Uploaded files data.
     */
    protected function submission_uploads($submission, $form_data)
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
        }

        return $uploads;
    }
}
