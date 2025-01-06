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
    private static $submission = null;

    protected function construct(...$args)
    {
        parent::construct(...$args);
    }

    protected function init()
    {
        add_action('ninja_forms_after_submission', function ($submission) {
            self::$submission = $submission;
            $this->do_submission($submission);
        });
    }

    public function form()
    {
        $form_data = !empty($_POST['formData'])
            ? json_decode(stripslashes($_POST['formData']), true)
            : ['id' => null];
        $form_id = (int) $form_data['id'];
        if (empty($form_id)) {
            return null;
        }

        return $this->get_form_by_id($form_id);
    }

    public function get_form_by_id($form_id)
    {
        return $this->serialize_form(Ninja_Forms()->form($form_id));
    }

    public function forms()
    {
        $forms = Ninja_Forms()->form()->get_forms();

        return array_map(function ($form) {
            $form = Ninja_Forms()->form($form->get_id());
            return $this->serialize_form($form);
        }, $forms);
    }

    public function submission()
    {
        if (empty(self::$submission)) {
            return null;
        }

        return $this->serialize_submission(self::$submission, $this->form());
    }

    public function uploads()
    {
        return [];
    }

    public function serialize_form($form_factory)
    {
        $form = $form_factory->get();
        $form_id = (int) $form->get_id();

        return [
            '_id' => 'ninja:' . $form_id,
            'id' => $form_id,
            'title' => $form->get_setting('title'),
            'hooks' => apply_filters(
                'forms_bridge_form_hooks',
                [],
                'ninja',
                $form_id
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

    private function _serialize_field($id, $settings)
    {
        $type = $this->norm_field_type($settings['type']);
        return [
            'id' => $id,
            'type' => $type,
            'name' => $settings['key'],
            'label' => $settings['label'],
            'required' => isset($settings['required'])
                ? $settings['required'] === '1'
                : false,
            'options' => isset($settings['options'])
                ? $settings['options']
                : [],
            'is_file' => $type === 'file',
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

    public function serialize_submission($submission, $form_data)
    {
        $data = [
            'submission_id' => $submission['actions']['save']['sub_id'],
        ];

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

    private function format_field_value($type, $value)
    {
        if ($type === 'hidden') {
            $number_val = (float) $value;
            if ((string) $number_val === $value) {
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

    protected function submission_uploads($submission, $form_data)
    {
        return [];
    }
}
