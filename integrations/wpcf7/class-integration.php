<?php

namespace FORMS_BRIDGE\WPCF7;

use FORMS_BRIDGE\Forms_Bridge;
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
     * Binds form submit hook to the do_submission routine.
     */
    protected function init()
    {
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

                Forms_Bridge::do_submission();
            },
            10,
            2
        );
    }

    /**
     * Retrives the current contact form's data.
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
     * Retrives a contact form's data by ID.
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
     * Retrives available constact forms as form data.
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
     * Creates a form from the given template fields.
     *
     * @param array $data Form template data.
     *
     * @return int|null ID of the new form.
     *
     * @todo Fix form email attribute.
     */
    public function create_form($data)
    {
        if (empty($data['title']) || empty($data['fields'])) {
            return;
        }

        $form = $this->fields_to_form($data['fields']);

        $contact_form = wpcf7_save_contact_form([
            'title' => $data['title'],
            'locale' => apply_filters(
                'wpct_i18n_current_language',
                null,
                'locale'
            ),
            'form' => $form,
            'mail' => get_option('admin_email'),
        ]);

        if (!$contact_form) {
            return;
        }

        return $contact_form->id();
    }

    /**
     * Removes a form by ID.
     *
     * @param integer $form_id Form ID.
     *
     * @return boolean Removal result.
     */
    public function remove_form($form_id)
    {
        $result = wp_delete_post($form_id);
        return !!$result;
    }

    /**
     * Retrives the current submission data.
     *
     * @return array Submission data.
     */
    public function submission()
    {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return null;
        }

        $form = $this->form();
        return $this->serialize_submission($submission, $form);
    }

    /**
     * Retrives the current submission uploaded files.
     *
     * @return array Uploaded files data.
     */
    public function uploads()
    {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return null;
        }

        return $this->submission_uploads($submission);
    }

    /**
     * Serializes a contact form instance as array data.
     *
     * @param WPCF7_ContactForm $form Form instance.
     *
     * @return array.
     */
    public function serialize_form($form)
    {
        $form_id = (int) $form->id();
        return [
            '_id' => 'wpcf7:' . $form_id,
            'id' => $form_id,
            'title' => $form->title(),
            'bridges' => apply_filters(
                'forms_bridge_bridges',
                [],
                null,
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
     * Serializes a form tags as array data.
     *
     * @param WPCF7_FormTag $field Form tag instance.
     * @param array $form_data Form data.
     *
     * @return array.
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

        // $type = $this->norm_field_type($type);

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

    // private function norm_field_type($type)
    // {
    //     switch ($type) {
    //         case 'iban':
    //         case 'vat':
    //         case 'email':
    //         case 'url':
    //         case 'textarea':
    //         case 'quiz':
    //             return 'text';
    //         case 'select':
    //         case 'checkbox':
    //         case 'radio':
    //             return 'options';
    //         case 'files':
    //             return 'file';
    //         case 'acceptance':
    //             return 'consent';
    //         default:
    //             return $type;
    //     }
    // }

    /**
     * Serializes the form's submission data.
     *
     * @param WPCF7_Submission $submission Submission instance.
     * @param array $form Form data.
     *
     * @return array Submission data.
     */
    public function serialize_submission($submission, $form_data)
    {
        $data = $submission->get_posted_data();

        foreach ($data as $key => $val) {
            $i = array_search($key, array_column($form_data['fields'], 'name'));
            $field = $form_data['fields'][$i];

            if ($field['type'] === 'hidden') {
                $number_val = (float) $val;
                if (strval($number_val) === $val) {
                    $data[$key] = $number_val;
                } else {
                    $data[$key] = $val;
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
     *
     * @return array Uploaded files data.
     */
    protected function submission_uploads($submission)
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

    /**
     * Gets form fields from a template and return a contact form content string.
     *
     * @param array $fields.
     *
     * @return string Form content.
     */
    private function fields_to_form($fields)
    {
        $form = '';
        foreach ($fields as $field) {
            if ($field['type'] == 'hidden') {
                $form .= $this->field_to_tag($field) . "\n\n";
            } else {
                $form .= "<label> {$field['label']}\n";
                $form .= '  ' . $this->field_to_tag($field) . " </label>\n\n";
            }
        }

        $form .= sprintf('[submit "%s"]', __('Submit', 'forms-bridge'));
        return $form;
    }

    /**
     * Gets a field template data and returns a form tag string.
     *
     * @param array $field.
     *
     * @return string.
     */
    private function field_to_tag($field)
    {
        if (!empty($field['value'])) {
            $type = 'hidden';
        } else {
            $type = sanitize_text_field($field['type']);

            if (($field['required'] ?? false) && $type !== 'hidden') {
                $type .= '*';
            }
        }

        $name = sanitize_text_field($field['name']);
        $tag = "[{$type} {$name} ";

        foreach ($field as $key => $val) {
            if (
                !in_array($key, ['name', 'type', 'value', 'required', 'label'])
            ) {
                $key = sanitize_text_field($key);
                $val = sanitize_text_field($val);
                $tag .= "{$key}:{$val} ";
            }
        }

        if (!empty($field['value'])) {
            $value = sanitize_text_field((string) $field['value']);
            $tag .= "\"{$value}\"";
        }

        return $tag . ']';
    }
}
