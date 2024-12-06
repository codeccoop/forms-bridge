<?php

namespace FORMS_BRIDGE\WPFORMS;

use FORMS_BRIDGE\Integration as BaseIntegration;
use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * WPForms integration.
 */
class Integration extends BaseIntegration
{
    /**
     * Inherit parent constructor and hooks submissions to wpforms_process_complete
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        add_action(
            'wpforms_process_complete',
            function ($fields, $entry, $form_data, $entry_id) {
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
     */
    protected function init()
    {
    }

    /**
     * Retrives the current WPForms_Form_Handler data.
     *
     * @return array Form data.
     */
    public function form()
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
     * Retrives form data by ID.
     *
     * @param int $form_id Form ID.
     *
     * @return array Form data.
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
     * Retrives available forms data.
     *
     * @return array Collection of form data.
     */
    public function forms()
    {
        $forms = wpforms()->obj('form')->get();
        return array_map(function ($form) {
            return $this->serialize_form($form);
        }, $forms);
    }

    /**
     * Retrives the current submission data.
     *
     * @return array Submission data.
     */
    public function submission()
    {
        $form = $this->form();
        if (!$form) {
            return null;
        }

        $submission = $_POST['wpforms'];
        $submission['fields'] = $_POST['wpforms']['complete'];
        return $this->serialize_submission($submission, $form);
    }

    /**
     * Retrives the current submission uploaded files.
     *
     * @return array Uploaded files data.
     */
    public function uploads()
    {
        $submission = $this->submission();
        if (!$submission) {
            return null;
        }

        return $this->submission_uploads($submission, $this->form());
    }

    /**
     * Serialize form data.
     *
     * @param WP_Post $form Form post instance.
     *
     * @return array Form data.
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
            'hooks' => apply_filters('forms_bridge_form_hooks', null, $form_id),
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
     * @param array $field WPForms field data representation.
     * @param array $form_data Form data.
     *
     * @return array Field data.
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
     * @param array $submission WPForms submission data.
     * @param array $form Form data.
     *
     * @return array Submission data.
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
     * @param object $submission WPForms submission data.
     * @param array $form_data Form data.
     *
     * @return array Uploaded files data.
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
