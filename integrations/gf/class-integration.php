<?php

namespace FORMS_BRIDGE\GF;

use FORMS_BRIDGE\Forms_Bridge;
use FORMS_BRIDGE\Integration as BaseIntegration;
use TypeError;
use GFAPI;
use GFCommon;
use GFFormDisplay;
use GFFormsModel;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'attachments.php';

/**
 * GravityForms integration.
 */
class Integration extends BaseIntegration
{
    /**
     * Binds after submission hook to the do_submission routine.
     */
    protected function init()
    {
        add_action('gform_after_submission', function () {
            Forms_Bridge::do_submission();
        });
    }

    /**
     * Retrives the current form's data.
     *
     * @return array.
     */
    public function form()
    {
        $form_id = null;
        if (isset($_POST['gform_submit'])) {
            require_once GFCommon::get_base_path() . '/form_display.php';
            $form_id = GFFormDisplay::is_submit_form_id_valid();
        }

        $form = GFAPI::get_form($form_id);
        if (empty($form) || !$form['is_active'] || $form['is_trash']) {
            return null;
        }

        if (is_wp_error($form)) {
            return null;
        }

        return $this->serialize_form($form);
    }

    /**
     * Retrives a form's data by ID.
     *
     * @param int $form_id Form ID.
     *
     * @return array.
     */
    public function get_form_by_id($form_id)
    {
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return null;
        }

        return $this->serialize_form($form);
    }

    /**
     * Retrives available forms' data.
     *
     * @return array Collection of form data array representations.
     */
    public function forms()
    {
        $forms = GFAPI::get_forms();
        return array_map(
            function ($form) {
                return $this->serialize_form($form);
            },
            array_filter($forms, function ($form) {
                return $form['is_active'] && !$form['is_trash'];
            })
        );
    }

    /**
     * Creates a form from a given template fields.
     *
     * @param array $data Form template data.
     *
     * @return int|null ID of the new form.
     */
    public function create_form($data)
    {
        if (empty($data['title']) || empty($data['fields'])) {
            return;
        }

        $data = array_merge($data, [
            'id' => 1,
            'fields' => $this->prepare_fields($data['fields']),
            'labelPlacement' => 'top_label',
            'useCurrentUserAsAuthor' => '1',
            'postAuthor' => '1',
            'postCategory' => '1',
            'postStatus' => 'publish',
            'button' => [
                'type' => 'text',
                'text' => esc_html__('Submit', 'forms-bridge'),
                'imageUrl' => '',
                'conditionalLogic' => null,
            ],
            'version' => '2.7',
        ]);

        $form_id = GFAPI::add_form($data);

        if (is_wp_error($form_id)) {
            return;
        }

        return $form_id;
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
        GFFormsModel::delete_form($form_id);
    }

    /**
     * Retrives the current submission data.
     *
     * @return array
     */
    public function submission()
    {
        $form_data = $this->form();
        if (!$form_data) {
            return null;
        }

        $submission = GFFormsModel::get_current_lead(
            GFAPI::get_form($form_data['id'])
        );

        if (!$submission) {
            return null;
        }

        $form = $this->form();
        return $this->serialize_submission($submission, $form);
    }

    /**
     * Retrives the current submission uploaded files.
     *
     * @return array Collection of uploaded files.
     */
    public function uploads()
    {
        $form_data = $this->form();
        if (!$form_data) {
            return null;
        }

        $submission = GFFormsModel::get_current_lead(
            GFAPI::get_form($form_data['id'])
        );

        if (!$submission) {
            return null;
        }

        $form = $this->form();
        return $this->submission_uploads($submission, $form);
    }

    /**
     * Serializes gf form's data.
     *
     * @param array $form GF form data.
     *
     * @return array
     */
    public function serialize_form($form)
    {
        $form_id = (int) $form['id'];
        return [
            '_id' => 'gf:' . $form_id,
            'id' => $form_id,
            'title' => $form['title'],
            'bridges' => apply_filters(
                'forms_bridge_bridges',
                [],
                'gf:' . $form_id
            ),
            'fields' => array_values(
                array_filter(
                    array_map(function ($field) {
                        return $this->serialize_field($field);
                    }, $form['fields'])
                )
            ),
        ];
        return $form;
    }

    /**
     * Serializes a GFField as array data.
     *
     * @param GFField $field Field object instance.
     *
     * @return array
     */
    private function serialize_field($field)
    {
        if (in_array($field->type, ['page', 'section', 'html', 'submit'])) {
            return;
        }

        if (strstr($field->type, 'post_')) {
            return;
        }

        $name = $field->inputName
            ? $field->inputName
            : ($field->adminLabel
                ? $field->adminLabel
                : $field->label);

        $inputs = $field->get_entry_inputs();
        if (is_array($inputs)) {
            $inputs = array_map(function ($input) {
                return [
                    'name' => $input['name'],
                    'label' => $input['label'],
                    'id' => $input['id'],
                ];
            }, $inputs);
        } else {
            $inputs = [];
        }

        $options = [];
        if (is_array($field->choices)) {
            $options = array_map(function ($opt) {
                return ['value' => $opt['value'], 'label' => $opt['text']];
            }, $field->choices);
        }

        return [
            'id' => $field->id,
            'type' => $field->type,
            'name' => $name,
            'label' => $field->label,
            'required' => $field->isRequired,
            'options' => $options,
            'inputs' => $inputs,
            'is_file' => $field->type === 'fileupload',
            'is_multi' =>
                $field->type === 'fileupload'
                    ? $field->multipleFiles
                    : $field->storageType === 'json' ||
                        $field->choiceLimit === 'unlimited' ||
                        $field->inputType === 'list' ||
                        $field->inputType === 'checkbox',
            'conditional' =>
                is_array($field->conditionalLogic) &&
                $field->conditionalLogic['enabled'],
        ];
    }

    // private function norm_field_type($type)
    // {
    //     switch ($type) {
    //         case 'multi_choice':
    //         case 'image_choice':
    //         case 'multiselect':
    //         case 'list':
    //         case 'option':
    //         case 'select':
    //         case 'radio':
    //         case 'checkbox':
    //             return 'options';
    //         case 'address':
    //         case 'website':
    //         case 'product':
    //         case 'email':
    //         case 'textarea':
    //         case 'name':
    //         case 'shipping':
    //             return 'text';
    //         case 'total':
    //         case 'quantity':
    //             return 'number';
    //         case 'fileupload':
    //             return 'file';
    //         default:
    //             return $type;
    //     }
    // }

    /**
     * Serializes the current form's submission data.
     *
     * @param array $submission GF form submission.
     * @param array $form Form data.
     *
     * @return array
     */
    public function serialize_submission($submission, $form_data)
    {
        $data = [];

        foreach ($form_data['fields'] as $field) {
            if ($field['is_file']) {
                continue;
            }

            $input_name = $field['name'];
            $inputs = $field['inputs'];

            if (!empty($inputs)) {
                // composed fields
                $isset = array_reduce(
                    $inputs,
                    function ($isset, $input) {
                        return $isset || $this->isset($input['id']);
                    },
                    false
                );

                if (!$isset) {
                    continue;
                }

                $names = array_map(function ($input) {
                    return $input['name'];
                }, $inputs);
                if (!empty(array_filter($names))) {
                    // Composed with subfields
                    foreach (array_keys($names) as $i) {
                        if (empty($names[$i])) {
                            continue;
                        }
                        $value = rgar($submission, (string) $inputs[$i]['id']);
                        $data[$names[$i]] = $value;
                    }
                } else {
                    // Plain composed
                    $values = [];
                    foreach ($inputs as $input) {
                        $value = rgar($submission, (string) $input['id']);
                        if ($input_name && $value) {
                            $value = $this->format_value(
                                $value,
                                $field,
                                $input
                            );

                            if ($value !== null) {
                                $values[] = $value;
                            }
                        }
                    }

                    if ($field['type'] === 'consent') {
                        $data[$input_name] = $values[0] ?? false;
                    } elseif ($field['type'] === 'name') {
                        $data[$input_name] = implode(' ', $values);
                    } elseif ($field['type'] === 'product') {
                        $data[$input_name] = $values[0];
                    } elseif ($field['type'] === 'address') {
                        $data[$input_name] = implode(', ', $values);
                    } else {
                        $data[$input_name] = $values;
                    }
                }
            } else {
                // simple fields
                $isset = $this->isset($field['id']);
                if (!$isset) {
                    continue;
                }

                if ($input_name) {
                    $raw_value = rgar($submission, (string) $field['id']);
                    $data[$input_name] = $this->format_value(
                        $raw_value,
                        $field
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Formats field values with noop fallback.
     *
     * @param mixed $value Field's value.
     * @param GFField $field Field object instance.
     * @param array $input Field's input data.
     *
     * @return mixed Formatted value.
     */
    private function format_value($value, $field, $input = null)
    {
        try {
            switch ($field['type']) {
                case 'consent':
                    if (preg_match('/\.1$/', $input['id'])) {
                        return $value === '1';
                    }

                    return null;
                case 'hidden':
                    $number_val = (float) $value;
                    if (strval($number_val) === $value) {
                        return $number_val;
                    }
                    break;
                case 'number':
                    return (float) preg_replace('/[^0-9\.,]/', '', $value);
                case 'list':
                    return maybe_unserialize($value);
                case 'multiselect':
                    return json_decode($value);
                case 'option':
                case 'shipping':
                    if (preg_match('/\|(.+$)/', $value, $matches)) {
                        return $matches[1];
                    }
            }
        } catch (TypeError) {
            // do nothing
        }

        return $value;
    }

    /**
     * Gets the current submission's uploaded files.
     *
     * @param array $submission GF submission data.
     * @param array $form_data Form data.
     *
     * @return array Uploaded files data.
     */
    protected function submission_uploads($submission, $form_data)
    {
        $private_upload = forms_bridge_private_upload($form_data['id']);

        return array_reduce(
            array_filter($form_data['fields'], function ($field) {
                return $field['is_file'];
            }),
            function ($carry, $field) use ($submission, $private_upload) {
                $paths = rgar($submission, (string) $field['id']);
                if (empty($paths)) {
                    return $carry;
                }

                $paths = $field['is_multi'] ? json_decode($paths) : [$paths];
                $paths = array_map(function ($path) use ($private_upload) {
                    if ($private_upload) {
                        $url = wp_parse_url($path);
                        parse_str($url['query'], $query);
                        $path = forms_bridge_attachment_fullpath(
                            $query['forms-bridge-attachment']
                        );
                    }

                    return $path;
                }, $paths);

                $carry[$field['name']] = [
                    'path' => $field['is_multi'] ? $paths : $paths[0],
                    'is_multi' => $field['is_multi'],
                ];

                return $carry;
            },
            []
        );
    }

    /**
     * Helper function to check if field is set on $_POST super global.
     *
     * @param string $field_id ID of the field.
     *
     * @return boolean
     */
    private function isset($field_id)
    {
        $key = 'input_' . implode('_', explode('.', $field_id));
        return isset($_POST[$key]);
    }

    /**
     * Decorate bridge's tempalte form fields data to be created as gf fields.
     *
     * @param array $fields Array with bridge's template form fields data.
     *
     * @return array Decorated array of fields.
     */
    private function prepare_fields($fields)
    {
        $gf_fields = [];
        for ($i = 0; $i < count($fields); $i++) {
            $id = $i + 1;
            $field = $fields[$i];
            $args = [
                $id,
                $field['name'],
                $field['label'] ?? '',
                $field['required'] ?? false,
            ];

            switch ($field['type']) {
                case 'hidden':
                    $args[] = $field['value'] ?? '';
                    $gf_fields[] = $this->hidden_field(...$args);
                    break;
                case 'number':
                    $gf_fields[] = $this->number_field(...$args);
                    break;
                case 'email':
                    $gf_fields[] = $this->email_field(...$args);
                    break;
                case 'options':
                    $args[] = $field['options'] ?? [];
                    $args[] = $field['is_multi'] ?? false;
                    $gf_fields[] = $this->options_field(...$args);
                    break;
                case 'textarea':
                    $gf_fields[] = $this->textarea_field(...$args);
                    break;
                case 'url':
                    $gf_fields[] = $this->url_field(...$args);
                    break;
                case 'file':
                    $args[] = $field['is_multi'] ?? false;
                    $args[] = $field['filetypes'] ?? '';
                    $gf_fields[] = $this->file_field(...$args);
                    break;
                // case 'text':
                default:
                    $gf_fields[] = $this->text_field(...$args);
            }
        }

        return $gf_fields;
    }

    /**
     * Returns a default field array data. Used as template for the field creation methods.
     *
     * @param string $type Field type.
     * @param int $id Field id.
     * @param string $name Input name.
     * @param string $label Field label.
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function field_template($type, $id, $name, $label, $required)
    {
        return [
            'type' => $type,
            'id' => (int) $id,
            'isRequired' => (bool) $required,
            'size' => 'large',
            'errorMessage' => __('please supply a valid value', 'forms-bridge'),
            'label' => $label,
            'formId' => 84,
            'inputType' => '',
            'displayOnly' => '',
            'inputs' => null,
            'choices' => '',
            'conditionalLogic' => '',
            'labelPlacement' => '',
            'descriptionPlacement' => '',
            'subLabelPlacement' => '',
            'placeholder' => '',
            'multipleFiles' => false,
            'maxFiles' => '',
            'calculationFormula' => '',
            'calculationRounding' => '',
            'enableCalculation' => '',
            'disableQuantity' => false,
            'displayAllCategories' => false,
            'inputMask' => false,
            'inputMaskValue' => '',
            'allowsPrepopulate' => false,
            'useRichTextEditor' => false,
            'visibility' => 'visible',
            'fields' => '',
            'inputMaskIsCustom' => false,
            'layoutGroupId' => '17f293c9',
            'autocompleteAttribute' => '',
            'emailConfirmEnabled' => false,
            'adminLabel' => '',
            'description' => '',
            'maxLength' => '',
            'cssClass' => '',
            'inputName' => $name,
            'noDuplicates' => false,
            'defaultValue' => '',
            'enableAutocomplete' => false,
        ];
    }

    /**
     * Returns a valid email field data.
     *
     * @param int $id Field id.
     * @param string $name Input name.
     * @param string $label Field label.
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function email_field($id, $name, $label, $required)
    {
        return array_merge(
            $this->field_template('email', $id, $name, $label, $required),
            [
                'errorMessage' => __(
                    'please supply a valid email address',
                    'forms-bridge'
                ),
                'enableAutocomplete' => true,
            ]
        );
    }

    /**
     * Returns a valid textarea field data.
     *
     * @param int $id Field id.
     * @param string $name Input name.
     * @param string $label Field label.
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function textarea_field($id, $name, $label, $required)
    {
        return $this->field_template('textarea', $id, $name, $label, $required);
    }

    /**
     * Returns a valid multi options field data, as a select field if is single, as
     * a checkbox field if is multiple.
     *
     * @param int $id Field id.
     * @param string $name Input name.
     * @param string $label Field label.
     * @param boolean $required Is field required.
     * @param array $options Options data.
     * @param boolean $is_multi Is field multi value
     *
     * @return array
     */
    private function options_field(
        $id,
        $name,
        $label,
        $required,
        $options,
        $is_multi
    ) {
        $choices = array_map(function ($opt) {
            return [
                'text' => esc_html($opt['label']),
                'value' => $opt['value'],
                'isSelected' => false,
                'price' => '',
            ];
        }, $options);

        if ($is_multi) {
            $inputs = [];
            for ($i = 0; $i < count($choices); $i++) {
                $input_id = $i + 1;
                $inputs[] = [
                    'id' => $id . '.' . $input_id,
                    'label' => $choices[$i]['label'],
                    'name' => '',
                ];
            }

            return array_merge(
                $this->field_template(
                    'checkbox',
                    $id,
                    $name,
                    $label,
                    $required
                ),
                [
                    'choices' => $choices,
                    'inputs' => $inputs,
                    'enableChoiceValue' => true,
                ]
            );
        } else {
            return array_merge(
                $this->field_template('select', $id, $name, $label, $required),
                [
                    'choices' => $choices,
                    'enableChoiceValue' => true,
                ]
            );
        }
    }

    /**
     * Returns a valid file-upload field data.
     *
     * @param int $id Field id.
     * @param string $name Input name.
     * @param string $label Field label.
     * @param boolean $required Is field required.
     * @param boolean $is_mulit Is field multi value?
     * @param string $filetypes String with allowed file extensions separated by commas.
     *
     * @return array
     */
    private function file_field(
        $id,
        $name,
        $label,
        $required,
        $is_multi,
        $filetypes
    ) {
        return array_merge(
            $this->field_template('fileupload', $id, $name, $label, $required),
            [
                'allowedExtensions' => (string) $filetypes,
                'multipleFiles' => (bool) $is_multi,
            ]
        );
    }

    /**
     * Returns a valid hidden field data.
     *
     * @param int $id Field id.
     * @param string $name Input name.
     * @param string $label Field label (unused).
     * @param boolean $required Is field required (unused).
     * @param string $value Field's default value.
     *
     * @return array
     */
    private function hidden_field($id, $name, $label, $required, $value)
    {
        return array_merge(
            $this->field_template('hidden', $id, $name, $name, true),
            [
                'inputType' => 'hidden',
                'defaultValue' => $value,
            ]
        );
    }

    /**
     * Returns a valid hidden field data.
     *
     * @param int $id Field id.
     * @param string $name Input name.
     * @param string $label Field label.
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function url_field($id, $name, $label, $required)
    {
        return $this->field_template('website', $id, $name, $label, $required);
    }

    /**
     * Returns a valid hidden field data.
     *
     * @param int $id Field id.
     * @param string $name Input name.
     * @param string $label Field label.
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function text_field($id, $name, $label, $required)
    {
        return array_merge(
            $this->field_template('text', $id, $name, $label, $required),
            [
                'inputType' => 'text',
            ]
        );
    }

    /**
     * Returns a valid hidden field data.
     *
     * @param int $id Field id.
     * @param string $name Input name.
     * @param string $label Field label.
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function number_field($id, $name, $label, $required)
    {
        return array_merge(
            $this->field_template('number', $id, $name, $label, $required),
            [
                'inputType' => 'number',
                'numberFormat' => 'decimal_dot',
            ]
        );
    }
}
