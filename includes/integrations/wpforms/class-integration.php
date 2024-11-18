<?php

namespace FORMS_BRIDGE\WPFORMS;

use FORMS_BRIDGE\Integration as BaseIntegration;
use WP_Post;
use WPForms_Field_File_Upload;

/**
 * WPForms integration.
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

        add_action(
            'wpforms_process_complete',
            function ($fields, $entry, $form_data, $entry_id) {
                // $submission = wpforms()->obj('submission');
                // $submission->register($fields, $entry, $form_data['id'], $form_data);
                $entry['fields'] = $fields;
                $entry['entry_id'] = $entry_id;
                $this->do_submission($entry, $form_data);
            },
            10,
            4
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
     * Retrive the current WPForms_Form_Handler data.
     *
     * @return array $form_data Form data array representation.
     */
    public function get_form()
    {
        $form_id = !empty($_POST['wpforms']['id'])
            ? absint($_POST['wpforms']['id'])
            : 0;
        if (!$form_id) {
            return null;
        }

        $form = wpforms()->obj('form')->get($form_id);
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
        $form = wpforms()->obj('form')->get($form_id);
        if (!$form) {
            return null;
        }

        return $this->serialize_form($form);
    }

    /**
     * Retrive available forms data.
     *
     * @since 3.0.0
     *
     * @return array $forms Collection of form data.
     */
    public function get_forms()
    {
        $forms = wpforms()->obj('form')->get();
        return array_map(function ($form) {
            return $this->serialize_form($form);
        }, $forms);
    }

    /**
     * Retrive the current submission data.
     *
     * @since 3.0.0
     *
     * @return array $submission Submission data.
     */
    public function get_submission()
    {
        $form = $this->get_form();
        if (!$form) {
            return null;
        }

        $submission = $_POST['wpforms'];
        $submission['fields'] = $_POST['wpforms']['complete'];
        return $this->serialize_submission($submission, $form);
    }

    /**
     * Retrive the current submission uploaded files.
     *
     * @since 3.0.0
     *
     * @return array $files Uploaded files data.
     */
    public function get_uploads()
    {
        $submission = $this->get_submission();
        if (!$submission) {
            return null;
        }

        return $this->submission_uploads($submission, $this->get_form());
    }

    /**
     * Serialize form data.
     *
     * @since 1.0.0
     *
     * @param object $form WPCF7_ContactForm instance.
     * @return array $form_data Form data.
     */
    public function serialize_form($form)
    {
        $data =
            $form instanceof WP_Post
                ? wpforms_decode($form->post_content)
                : $form;

        $form_id = (int) $data['id'];
        return [
            'id' => $form_id,
            'title' => $data['settings']['form_title'],
            'hooks' => apply_filters(
                'forms_bridge_form_hooks',
                null,
                $form_id
            ),
            'fields' => array_values(
                array_map(function ($field) {
                    return $this->serialize_field($field);
                }, $data['fields'])
            ),
        ];
    }

    /**
     * Serialize form field data.
     *
     * @since 1.0.0
     *
     * @param object $field WPCF7_FormTag instance.
     * @param array $form_data Form data.
     * @return array $field_data Field data.
     */
    private function serialize_field($field)
    {
        return [
            'id' => (int) $field['id'],
            'type' => $field['type'],
            'name' => $field['label'],
            'label' => $field['label'],
            'required' => $field['required'] == '1',
            'options' => isset($field['choices']) ? $field['choices'] : [],
            'conditional' => false,
        ];
    }

    /**
     * Serialize form submission data.
     *
     * @since 1.0.0
     *
     * @param object $submission Submission instance.
     * @param array $form Form data.
     * @return array $submission_data Submission data.
     */
    public function serialize_submission($submission, $form_data)
    {
        $data = [
            'submission_id' => $submission['entry_id'],
        ];

        foreach ($submission['fields'] as $field) {
            $data[$field['name']] = $field['value'];
        }

        return $data;
    }

    /**
     * Get form submission uploaded files.
     *
     * @since 1.0.0
     *
     * @param object $submission WPCF7_Submission instance.
     * @param array $form_data Form data.
     * @return array $uploads Uploaded files data.
     */
    protected function submission_uploads($submission, $form_data)
    {
        $fields = wpforms_get_form_fields($form_data['id'], ['file-upload']);
        if (empty($fields) || empty($_FILES)) {
            return [];
        }

        $files_keys = preg_filter(
            '/^/',
            'wpforms_' . $form_data['id'] . '_',
            array_keys($fields)
        );
        $files = wp_list_filter(wp_array_slice_assoc($_FILES, $files_keys), [
            'error' => 0,
        ]);

        $uploads = [];
        foreach ($fields as $field) {
            if (empty($files_paths)) {
                continue;
            }

            $is_multi = sizeof($paths) > 1;
            $uploads[$field['name']] = [
                'path' => $is_multi ? $paths : $paths[0],
                'is_multi' => $is_multi,
            ];
        }

        return $uploads;
    }
}
