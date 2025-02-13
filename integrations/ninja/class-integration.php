<?php

namespace FORMS_BRIDGE\NINJA;

use Forms_Bridge\Integration as BaseIntegration;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Ninja Forms integration
 */
class Integration extends BaseIntegration
{
    /**
     * Handles the current submission data.
     *
     * @var array|null
     */
    private static $submission = null;

    /**
     * Binds after submission hook to the do_submission routine.
     */
    protected function init()
    {
        add_action('ninja_forms_after_submission', function ($submission) {
            self::$submission = $submission;
            $this->do_submission();
        });
    }

    /**
     * Retrives the current form's data.
     *
     * @return array.
     */
    public function form()
    {
        $form_data = !empty($_POST['formData'])
            ? json_decode(
                sanitize_text_field(wp_unslash($_POST['formData'])),
                true
            )
            : ['id' => null];
        $form_id = (int) $form_data['id'];
        if (empty($form_id)) {
            return null;
        }

        return $this->get_form_by_id($form_id);
    }

    /**
     * Retrives a form model's data by ID.
     *
     * @return array.
     */
    public function get_form_by_id($form_id)
    {
        return $this->serialize_form(Ninja_Forms()->form($form_id));
    }

    /**
     * Retrives available form models' data.
     *
     * @return array Collection of forms data.
     */
    public function forms()
    {
        $forms = Ninja_Forms()->form()->get_forms();

        return array_map(function ($form) {
            $form = Ninja_Forms()->form($form->get_id());
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
     * @todo Implement this routine.
     */
    public function create_form($data) {}

    /**
     * Removes a form by ID.
     *
     * @param integer $form_id Form ID.
     *
     * @return boolean Removal result.
     *
     * @todo Implement this routine.
     */
    public function remove_form($form_id) {}

    /**
     * Retrives the current form submission data.
     *
     * @return array
     */
    public function submission()
    {
        if (empty(self::$submission)) {
            return null;
        }

        $form = $this->form();
        return $this->serialize_submission(self::$submission, $form);
    }

    /**
     * Retrives the current submission uploaded files.
     *
     * @return array Collection of uploaded files.
     *
     * @todo Adapt to premium version with available upload field.
     */
    public function uploads()
    {
        return [];
    }

    /**
     * Serializes a ninja form model instance as array data.
     *
     * @param NF_Abstracts_ModelFactory $form Form factory instance.
     *
     * @return array
     */
    public function serialize_form($form_factory)
    {
        $form = $form_factory->get();
        $form_id = (int) $form->get_id();

        return [
            '_id' => 'ninja:' . $form_id,
            'id' => $form_id,
            'title' => $form->get_setting('title'),
            'bridges' => apply_filters(
                'forms_bridge_bridges',
                [],
                'ninja:' . $form_id
            ),
            'fields' => array_values(
                array_filter(
                    array_map(function ($field) {
                        return $this->serialize_field($field);
                    }, $form_factory->get_fields())
                )
            ),
        ];
    }

    /**
     * Serializes a form field model instance as array data.
     *
     * @param NF_Database_Models_Field $field Form field model instance.
     *
     * @return array
     */
    private function serialize_field($field)
    {
        if (in_array($field->get_setting('type'), ['html', 'hr', 'submit'])) {
            return;
        }

        return $this->_serialize_field(
            $field->get_id(),
            $field->get_settings()
        );
    }

    /**
     * Serializes field settings as field data array.
     *
     * @param int $id Field id.
     * @param array $settings Field settings data.
     *
     * @return array.
     */
    private function _serialize_field($id, $settings)
    {
        // $type = $this->norm_field_type($settings['type']);
        return [
            'id' => $id,
            'type' => $settings['type'],
            'name' => $settings['key'],
            'label' => $settings['label'],
            'required' => isset($settings['required'])
                ? $settings['required'] === '1'
                : false,
            'options' => isset($settings['options'])
                ? $settings['options']
                : [],
            'is_file' => false, // $settings['type'] === 'file',
            'is_multi' => in_array($settings['type'], [
                'listmultiselect',
                'listcheckbox',
            ]),
            'conditional' => false,
            'children' => isset($settings['fields'])
                ? array_map(function ($setting) {
                    return $this->_serialize_field($setting['id'], $setting);
                }, $settings['fields'])
                : [],
        ];
    }

    private function norm_field_type($type)
    {
        switch ($type) {
            case 'textbox':
            case 'lastname':
            case 'firstname':
            case 'address':
            case 'zip':
            case 'phone':
            case 'city':
            case 'spam':
            case 'email':
            case 'textarea':
                return 'text';
            case 'listcountry':
            case 'listselect':
            case 'listmultiselect':
            case 'listimage':
            case 'listradio':
            case 'listcheckbox':
            case 'select':
            case 'radio':
            case 'checkbox':
                return 'options';
            case 'starrating':
                return 'number';
            default:
                return $type;
        }
    }

    /**
     * Serialize the form's submission data.
     *
     * @param array $submission Submission data.
     * @param array $form_data Form data.
     *
     * @return array.
     */
    public function serialize_submission($submission, $form_data)
    {
        $data = [];

        foreach ($form_data['fields'] as $field_data) {
            $field = $submission['fields_by_key'][$field_data['name']];

            if ($field_data['type'] === 'file') {
                continue;
            }

            if ($field_data['type'] === 'repeater') {
                $subfields = $field['fields'];
                $values = $field['value'];
                $fieldset = [];
                $i = 0;
                foreach (array_values($values) as $value) {
                    $row_index = floor($i / count($subfields));
                    $row =
                        count($fieldset) == $row_index
                            ? []
                            : $fieldset[$row_index];

                    $field_index = $i % count($subfields);
                    $child_field = $field['fields'][$field_index];

                    $row[$child_field['label']] = $this->format_field_value(
                        $child_field['type'],
                        $value['value']
                    );

                    $fieldset[$row_index] = $row;
                    $i++;
                }
                $data[$field['label']] = $fieldset;
            } else {
                $data[$field['label']] = $this->format_field_value(
                    $field_data['type'],
                    $field['value']
                );
            }
        }

        return $data;
    }

    /**
     * Formats field values based on its type.
     *
     * @param string $type Field type.
     * @param mixed $value Raw field value.
     *
     * @return mixed Formated value.
     */
    private function format_field_value($type, $value)
    {
        if ($type === 'hidden') {
            $number_val = (float) $value;
            if (strval($number_val) === $value) {
                return $number_val;
            } else {
                return $value;
            }
        } elseif ($type === 'number') {
            return (float) $value;
        } else {
            return $value;
        }
    }

    /**
     * Gets submission uploaded files.
     *
     * @param WPCF7_Submission $submission Submission instance.
     * @param array $form_data Form data.
     *
     * @return array Uploaded files data.
     *
     * @todo Adapt to premium version with available upload field.
     */
    protected function submission_uploads($submission, $form_data)
    {
        return [];
    }
}
