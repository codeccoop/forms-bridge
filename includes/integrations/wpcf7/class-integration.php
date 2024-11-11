<?php

namespace WPCT_ERP_FORMS\WPCF7;

use WPCT_ERP_FORMS\Integration as BaseIntegration;
use WPCF7_ContactForm;
use WPCF7_Submission;

/**
 * ContactForm7 integration.
 *
 * @since 1.0.0
 */
class Integration extends BaseIntegration
{
    /**
     * Inherit parent constructor and hooks submissions to wpcf7_before_send_mail
     *
     * @since 0.0.1
     */
    protected function __construct()
    {
        parent::__construct();

        add_filter(
            'wpcf7_before_send_mail',
            function ($form, &$abort, $submission) {
                $this->do_submission($submission, $form);
            },
            10,
            3
        );
    }

    /**
     * Integration initializer to be fired on wp init.
     *
     * @since 0.0.1
     */
    protected function init()
    {
    }

    /**
     * Retrive the current WPCF7_ContactForm data.
     *
     * @return array $form_data Form data array representation.
     */
    public function get_form()
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
     * @since 3.0.0
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
     * Retrive available integration forms data.
     *
     * @since 3.0.0
     *
     * @return array $forms Collection of form data.
     */
    public function get_forms()
    {
        $forms = WPCF7_ContactForm::find(['post_status', 'publish']);
        return array_map(function ($form) {
            return $this->serialize_form($form);
        }, $forms);
    }

    /**
     * Retrive the current WPCF7_Submission data.
     *
     * @since 3.0.0
     *
     * @return array $submission Submission data.
     */
    public function get_submission()
    {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return null;
        }

        return $this->serialize_submission($submission, $this->get_form());
    }

    /**
     * Retrive the current WPCF7_Submission uploaded files.
     *
     * @since 3.0.0
     *
     * @return array $files Uploaded files data.
     */
    public function get_uploads()
    {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return null;
        }

        return $this->submission_uploads($submission, $this->get_form());
    }

    /**
     * Serialize WPCF7_ContactForm data.
     *
     * @since 1.0.0
     *
     * @param object $form WPCF7_ContactForm instance.
     * @return array $form_data Form data.
     */
    public function serialize_form($form)
    {
        $form_id = $form->id();
        return [
            'id' => $form_id,
            'title' => $form->title(),
            'hooks' => apply_filters(
                'wpct_erp_forms_form_hooks',
                null,
                $form_id
            ),
            'fields' => array_map(
                function ($field) {
                    return $this->serialize_field($field);
                },
                array_values(
                    array_filter($form->scan_form_tags(), function ($tag) {
                        return $tag->basetype !== 'response' &&
                            $tag->basetype !== 'submit';
                    })
                )
            ),
        ];
    }

    /**
     * Serialize WPCF7_FormTag to array.
     *
     * @since 1.0.0
     *
     * @param object $field WPCF7_FormTag instance.
     * @param array $form_data Form data.
     * @return array $field_data Field data.
     */
    private function serialize_field($field)
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
            'conditional' =>
                $field->basetype === 'conditional' ||
                $field->basetype === 'fileconditional',
        ];
    }

    /**
     * Serialize the WPCF7_Submission data.
     *
     * @since 1.0.0
     *
     * @param object $submission WPCF7_Submission instance.
     * @param array $form Form data.
     * @return array $submission_data Submission data.
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
                // } elseif (!in_array($field['type'], ['checkbox', 'select']) && is_array($val)) {
            } elseif (is_array($val) && !is_array($_POST[$key])) {
                $data[$key] = $val[0];
            } elseif (
                $field['type'] === 'file' ||
                $field['type'] === 'submit'
            ) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Get WPCF7_Submission uploaded files.
     *
     * @since 1.0.0
     *
     * @param object $submission WPCF7_Submission instance.
     * @param array $form_data Form data.
     * @return array $uploads Uploaded files data.
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
