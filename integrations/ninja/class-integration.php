<?php

namespace FORMS_BRIDGE\NINJA;

use FORMS_BRIDGE\Forms_Bridge;
use FORMS_BRIDGE\Integration as BaseIntegration;
use NF_Database_FieldsController;

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
    public function create_form($data)
    {
        $title = sanitize_text_field($data['title']);
        $form_data = $this->form_template($title);
        $form_data['fields'] = $this->decorate_form_fields($data['fields']);
        $form_data['settings']['form_content_data'] = array_map(function (
            $field
        ) {
            return $field['key'];
        }, $form_data['fields']);

        $form = Ninja_Forms()->form()->get();
        $form->save();

        $form_data['id'] = $form->get_id();

        $form->update_settings($form_data['settings'])->save();

        $db_fields_controller = new NF_Database_FieldsController(
            $form_data['id'],
            $form_data['fields']
        );
        $db_fields_controller->run();

        foreach ($form_data['actions'] as $action_data) {
            $action_data['parent_id'] = $form_data['id'];
            $action = Ninja_Forms()->form()->action()->get();
            $action->save();
            $action_data['id'] = $action->get_id();
            $action->update_settings($action_data)->save();
        }

        return $form_data['id'];
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
        $form = Ninja_Forms()->form($form_id)->get();
        if ($form) {
            return $form->delete();
        }

        return false;
    }

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
        return [
            'id' => $id,
            'type' => $settings['type'],
            'name' =>
                $settings['custom_name_attribute'] ?:
                $settings['admin_label'] ?:
                $settings['label'],
            'label' => $settings['label'],
            'required' => isset($settings['required'])
                ? $settings['required'] === '1'
                : false,
            'options' => isset($settings['options'])
                ? $settings['options']
                : [],
            'is_file' => false,
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

    // private function norm_field_type($type)
    // {
    //     switch ($type) {
    //         case 'textbox':
    //         case 'lastname':
    //         case 'firstname':
    //         case 'address':
    //         case 'zip':
    //         case 'phone':
    //         case 'city':
    //         case 'spam':
    //         case 'email':
    //         case 'textarea':
    //             return 'text';
    //         case 'listcountry':
    //         case 'listselect':
    //         case 'listmultiselect':
    //         case 'listimage':
    //         case 'listradio':
    //         case 'listcheckbox':
    //         case 'select':
    //         case 'radio':
    //         case 'checkbox':
    //             return 'options';
    //         case 'starrating':
    //             return 'number';
    //         default:
    //             return $type;
    //     }
    // }

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
     * @todo Ninja forms uploads addon for premium licenses
     */
    protected function submission_uploads($submission, $form_data)
    {
        return [];
    }

    private function decorate_form_fields($fields)
    {
        $nf_fields = [];
        for ($i = 0; $i < count($fields); $i++) {
            $order = $i + 1;
            $field = $fields[$i];

            $args = [
                $order,
                $field['name'],
                $field['label'] ?? $field['name'],
                $field['required'] ?? false,
            ];
            switch ($field['type']) {
                case 'text':
                    $nf_fields[] = $this->text_field(...$args);
                    break;
                case 'textarea':
                    $nf_fields[] = $this->textarea_field(...$args);
                    break;
                case 'email':
                    $nf_fields[] = $this->email_field(...$args);
                    break;
                case 'hidden':
                    $args[] = $field['value'] ?? '';
                    $nf_fields[] = $this->hidden_field(...$args);
                    break;
                case 'options':
                    $args[] = $field['options'] ?? [];
                    $args[] = $field['is_multi'] ?? false;
                    $nf_fields[] = $this->options_field(...$args);
                    break;
                default:
                    $args = array_merge([$field['type']], $args);
                    $nf_fields[] = $this->field_template(...$args);
            }
        }

        $nf_fields[] = [
            'objectType' => 'Field',
            'objectDomain' => 'fields',
            'editActive' => false,
            'order' => count($nf_fields),
            'type' => 'submit',
            'label' => __('Submit', 'forms-bridge'),
            'processing_label' => __('Processing', 'forms-bridge'),
            'key' => 'submit',
        ];

        return array_map(function ($nf_field) {
            return [
                'id' => 'tmp-' . $nf_field['order'],
                'settings' => $nf_field,
            ];
        }, $nf_fields);
    }

    private function field_template($type, $order, $name, $label, $required)
    {
        return [
            'objectType' => 'Field',
            'objectDomain' => 'fields',
            'editActive' => false,
            'order' => (string) $order,
            'type' => $type,
            'label' => $label,
            'key' => $name,
            'custom_name_attribute' => $name,
            'admin_label' => '',
            'required' => $required ? '1' : '',
            'default' => '',
            'placeholder' => '',
            'container_class' => '',
            'label_pos' => 'default',
        ];
    }

    private function hidden_field($order, $name, $label, $required, $value)
    {
        return array_merge(
            $this->field_template('hidden', $order, $name, $label, $required),
            [
                'required' => '1',
                'default' => $value,
                'value' => $value,
            ]
        );
    }

    private function options_field(
        $order,
        $name,
        $label,
        $required,
        $options,
        $is_multi
    ) {
        $options = [];
        for ($i = 0; $i < count($options); $i++) {
            $options[] = [
                'label' => $options[$i]['label'],
                'value' => $options[$i]['value'],
                'order' => (string) $i,
                'calc' => '',
                'selected' => 0,
                'max_options' => 0,
                'errors' => [],
                'settingModel' => [
                    'settings' => false,
                    'hide_merge_tags' => false,
                    'error' => false,
                    'name' => 'options',
                    'type' => 'option-repeater',
                    'label' =>
                        'Options <a href="#" class="nf-add-new">Add New</a> <a href="#" class="extra nf-open-import-tooltip"><i class="fa fa-sign-in" aria-hidden="true"></i> Import</a>',
                    'width' => 'full',
                    'group' => '',
                    'value' => [
                        [
                            'label' => 'One',
                            'value' => 'one',
                            'calc' => '',
                            'selected' => 0,
                            'order' => 0,
                        ],
                        [
                            'label' => 'Two',
                            'value' => 'two',
                            'calc' => '',
                            'selected' => 0,
                            'order' => 0,
                        ],
                        [
                            'label' => 'Three',
                            'value' => 'three',
                            'calc' => '',
                            'selected' => 0,
                            'order' => 0,
                        ],
                    ],
                    'columns' => [
                        'label' => [
                            'header' => __('Label', 'forms-bridge'),
                            'default' => '',
                        ],
                        'value' => [
                            'header' => __('Value', 'forms-bridge'),
                            'default' => '',
                        ],
                        'calc' => [
                            'header' => __('Calc value', 'forms-bridge'),
                            'default' => '',
                        ],
                        'selected' => [
                            'header' =>
                                '<span class="dashicons dashicons-yes"></span>',
                            'default' => 0,
                        ],
                    ],
                ],
            ];
        }

        $type = $is_multi ? 'listcheckbox' : 'listselect';

        return array_merge(
            $this->field_template($type, $order, $name, $label, $required),
            [
                'options' => $options,
            ]
        );
    }

    private function text_field($order, $name, $label, $required)
    {
        return array_merge(
            $this->field_template('textbox', $order, $name, $label, $required),
            [
                'input_limit_type' => 'characters',
                'input_limit_msg' => __('Character(s) left', 'forms-bridge'),
                'drawerDisabled' => false,
                'manual_key' => false,
            ]
        );
    }

    private function textarea_field($order, $name, $label, $required)
    {
        return array_merge(
            $this->field_template('textarea', $order, $name, $label, $required),
            [
                'input_limit_type' => 'characters',
                'input_limit_msg' => __('Character(s) left', 'forms-bridge'),
                'drawerDisabled' => false,
                'manual_key' => false,
            ]
        );
    }

    private function email_field($order, $name, $label, $required)
    {
        return array_merge(
            $this->field_template('email', $order, $name, $label, $required),
            []
        );
    }

    private function form_template($title)
    {
        return [
            'id' => 'tmp-1',
            'settings' => [
                'objectType' => 'Form Setting',
                'editActive' => true,
                'key' => '',
                'title' => $title,
                'clear_complete' => '1',
                'hide_complete' => '1',
                'default_label_pos' => 'above',
                'show_title' => '0',
                'wrapper_class' => '',
                'element_class' => '',
                'add_submit' => '1',
                'calculations' => [],
                'formContentData' => [],
                'drawerDisabled' => false,
                'unique_field_error' => __(
                    'A form with this value has already been submitted.',
                    'forms-bridge'
                ),
                'sub_limit_msg' => __(
                    'The form has reached its submission limit.',
                    'forms-bridge'
                ),
                'logged_in' => false,
                'not_logged_in_msg' => '',
            ],
            'fields' => [],
            'actions' => [
                [
                    'title' => '',
                    'key' => '',
                    'type' => 'save',
                    'active' => '1',
                    'created_at' => date('Y-m-d h:i:s'),
                    'label' => __('Record Submission', 'forms-bridge'),
                    'objectType' => 'Action',
                    'objectDomain' => 'actions',
                    'editActive' => '',
                    'conditions' => [
                        'collapsed' => '',
                        'process' => '1',
                        'connector' => 'all',
                        'when' => [
                            [
                                'connector' => 'AND',
                                'key' => '',
                                'comparator' => '',
                                'value' => '',
                                'type' => 'field',
                                'modelType' => 'when',
                            ],
                        ],
                        'then' => [
                            [
                                'key' => '',
                                'trigger' => '',
                                'value' => '',
                                'type' => 'field',
                                'modelType' => 'then',
                            ],
                        ],
                        'else' => [],
                    ],
                    'payment_gateways' => '',
                    'payment_total' => '',
                    'tag' => '',
                    'to' => '',
                    'email_subject' => '',
                    'email_message' => '',
                    'from_name' => '',
                    'from_address' => '',
                    'reply_to' => '',
                    'email_format' => 'html',
                    'cc' => '',
                    'bcc' => '',
                    'attach_csv' => '',
                    'redirect_url' => '',
                    'email_message_plain' => '',
                ],
                [
                    'title' => '',
                    'key' => '',
                    'type' => 'successmessage',
                    'active' => '1',
                    'created_at' => date('Y-m-d h:i:s'),
                    'label' => __('Success message', 'forms-bridge'),
                    'message' => __(
                        'Thank you for filling out my form!',
                        'forms-bridge'
                    ),
                    'objectType' => 'Action',
                    'objectDomain' => 'actions',
                    'editActive' => '',
                    'conditions' => [
                        'collapsed' => '',
                        'process' => '1',
                        'connector' => 'all',
                        'when' => [
                            [
                                'connector' => 'AND',
                                'key' => '',
                                'comparator' => '',
                                'value' => '',
                                'type' => 'field',
                                'modelType' => 'when',
                            ],
                        ],
                        'then' => [
                            [
                                'key' => '',
                                'trigger' => '',
                                'value' => '',
                                'type' => 'field',
                                'modelType' => 'then',
                            ],
                        ],
                        'else' => [],
                    ],
                    'payment_gateways' => '',
                    'payment_total' => '',
                    'tag' => '',
                    'to' => '',
                    'email_subject' => '',
                    'email_message' => '',
                    'from_name' => '',
                    'from_address' => '',
                    'reply_to' => '',
                    'email_format' => 'html',
                    'cc' => '',
                    'bcc' => '',
                    'attach_csv' => '',
                    'redirect_url' => '',
                    'success_msg' => __(
                        '<p>Form submitted successfully.</p>',
                        'forms-bridge'
                    ),
                    'email_message_plain' => '',
                ],
            ],
        ];
    }
}
