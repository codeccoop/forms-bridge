<?php

namespace FORMS_BRIDGE\NINJA;

use FBAPI;
use FORMS_BRIDGE\Forms_Bridge;
use FORMS_BRIDGE\Integration as BaseIntegration;
use NF_Database_FieldsController;
use WPN_Helper;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Ninja Forms integration
 */
class Integration extends BaseIntegration
{
    public const name = 'ninja';

    public const title = 'Ninja Forms';

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
        $form_data['settings']['formContentData'] = array_map(function (
            $field
        ) {
            return $field['settings']['key'];
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
        $form_data['fields'] = $db_fields_controller->get_updated_fields_data();

        foreach ($form_data['actions'] as &$action_data) {
            $action_data['parent_id'] = $form_data['id'];
            $action = Ninja_Forms()->form()->action()->get();
            $action->save();
            $action_data['id'] = $action->get_id();
            $action->update_settings($action_data)->save();
        }

        WPN_Helper::update_nf_cache($form_data['id'], $form_data);

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

    public function submission_id()
    {
        $submission = $this->submission(true);
        if ($submission) {
            return (string) $submission['actions']['save']['sub_id'];
        }
    }

    /**
     * Retrives the current form submission data.
     *
     * @param boolean $raw Control if the submission is serialized before exit.
     *
     * @return array
     */
    public function submission($raw = false)
    {
        if (empty(self::$submission)) {
            return null;
        } elseif ($raw) {
            return self::$submission;
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
        $form_data = $this->form();
        if (!$form_data) {
            return null;
        }

        $submission = self::$submission;
        if (empty($submission)) {
            return null;
        }

        return $this->submission_uploads($submission, $form_data);
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
        $fields = array_filter(
            array_map(function ($field) use ($form) {
                return $this->serialize_field($field, $form->get_settings());
            }, $form_factory->get_fields())
        );

        return apply_filters(
            'forms_bridge_form_data',
            [
                '_id' => 'ninja:' . $form_id,
                'id' => $form_id,
                'title' => $form->get_setting('title'),
                'bridges' => FBAPI::get_form_bridges($form_id, 'ninja'),
                'fields' => array_values($fields),
            ],
            $form,
            'ninja'
        );
    }

    /**
     * Serializes a form field model instance as array data.
     *
     * @param NF_Database_Models_Field $field Form field model instance.
     * @param array $form_settings Form settings data.
     *
     * @return array
     */
    private function serialize_field($field, $form_settings)
    {
        if (
            in_array($field->get_setting('type'), [
                'html',
                'hr',
                'confirm',
                'recaptcha',
                'spam',
                'submit',
            ])
        ) {
            return;
        }

        return apply_filters(
            'forms_bridge_form_field_data',
            $this->_serialize_field(
                $field->get_id(),
                $field->get_settings(),
                $form_settings
            ),
            $field,
            'ninja'
        );
    }

    /**
     * Serializes field settings as field data array.
     *
     * @param int $id Field id.
     * @param array $settings Field settings data.
     * @param array $form_settings Form settings data.
     *
     * @return array.
     */
    private function _serialize_field($id, $settings, $form_settings)
    {
        $name =
            $settings['key'] ??
            ($settings['admin_label'] ?? $settings['label']);

        $children = isset($settings['fields'])
            ? array_map(function ($setting) use ($form_settings) {
                return $this->_serialize_field(
                    $setting['id'],
                    $setting,
                    $form_settings
                );
            }, $settings['fields'])
            : [];

        $is_conditional = false;
        $conditions = $form_settings['conditions'] ?? [];
        foreach ((array) $conditions as $condition) {
            $then = $condition['then'] ?? [];
            $else = $condition['else'] ?? [];
            foreach (array_merge($then, $else) as $effect) {
                if ($effect['type'] !== 'field') {
                    continue;
                }

                $is_conditional =
                    $effect['key'] === $settings['key'] &&
                    $effect['trigger'] === 'hide_field';
                if ($is_conditional) {
                    break;
                }
            }

            if ($is_conditional) {
                break;
            }
        }

        switch ($settings['type']) {
            case 'email':
                $type = 'email';
                break;
            case 'checkbox':
                $type = 'boolean';
                break;
            case 'date':
                $type = 'date';
                break;
            case 'select':
            case 'radio':
            case 'listradio':
            case 'listselect':
            case 'listcountry':
            case 'liststate':
            case 'listimage':
            case 'listmultiselect':
            case 'listcheckbox':
                $type = 'select';
                break;
            case 'starrating':
            case 'number':
                $type = 'number';
                break;
            case 'repeater':
                $type = 'mixed';
                break;
            case 'file_upload':
                $type = 'file';
                break;
            case 'textbox':
            case 'lastname':
            case 'firstname':
            case 'address':
            case 'zip':
            case 'city':
            case 'spam':
            case 'phone':
            case 'textarea':
            default:
                $type = 'text';
                break;
        }

        return [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'label' => $settings['label'],
            'required' => isset($settings['required'])
                ? $settings['required'] === '1'
                : false,
            'options' => isset($settings['options'])
                ? $settings['options']
                : [],
            'is_file' => $settings['type'] === 'file_upload',
            'is_multi' => $this->is_multi_field($settings),
            'conditional' => $is_conditional,
            'children' => $children,
            'format' => strtolower($settings['date_format'] ?? ''),
            'schema' => $this->field_value_schema($settings, $children),
        ];
    }

    /**
     * Checks if a filed is multi value field.
     *
     * @param array Field settings data.
     *
     * @return boolean
     */
    private function is_multi_field($settings)
    {
        if (
            in_array(
                $settings['type'],
                ['listmultiselect', 'listcheckbox', 'repeater'],
                true
            )
        ) {
            return true;
        }

        if (
            $settings['type'] === 'listimage' &&
            ($settings['allow_multi_select'] ?? false)
        ) {
            return true;
        }

        if (
            $settings['type'] === 'file_upload' &&
            $settings['upload_multi_count'] > 1
        ) {
            return true;
        }

        return false;
    }

    /**
     * Gets the field value JSON schema.
     *
     * @param array $settings Field settings data.
     * @param array $children Children fields.
     *
     * @return array JSON schema of the value of the field.
     */
    private function field_value_schema($settings, $children = [])
    {
        switch ($settings['type']) {
            case 'checkbox':
                return ['type' => 'boolean'];
            case 'textbox':
            case 'lastname':
            case 'firstname':
            case 'address':
            case 'zip':
            case 'city':
            case 'spam':
            case 'phone':
            case 'email':
            case 'textarea':
            case 'select':
            case 'radio':
            case 'checkbox':
            case 'date':
            case 'listradio':
            case 'listselect':
            case 'listcountry':
            case 'liststate':
                return ['type' => 'string'];
            case 'listimage':
                if ($settings['allow_multi_select'] ?? false) {
                    $items = [];
                    for ($i = 0; $i < count($settings['image_options']); $i++) {
                        $items[] = ['type' => 'string'];
                    }

                    return [
                        'type' => 'array',
                        'items' => $items,
                        'additionalItems' => false,
                    ];
                }

                return ['type' => 'string'];
            case 'listmultiselect':
            case 'listcheckbox':
                $items = [];
                for ($i = 0; $i < count($settings['options']); $i++) {
                    $items[] = ['type' => 'string'];
                }

                return [
                    'type' => 'array',
                    'items' => $items,
                    'additionalItems' => false,
                ];
            case 'starrating':
            case 'number':
                return ['type' => 'number'];
            case 'repeater':
                $i = 0;
                $properties = array_reduce(
                    $children,
                    function ($props, $child) use ($settings, &$i) {
                        $field = $settings['fields'][$i];
                        $props[$child['name']] = $this->field_value_schema(
                            $field
                        );
                        $i++;
                        return $props;
                    },
                    []
                );

                return [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => $properties,
                    ],
                    'additionalItems' => true,
                ];
            case 'file_upload':
                return;
            default:
                return ['type' => 'string'];
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
            if ($field_data['is_file']) {
                continue;
            }

            $field = $submission['fields'][(int) $field_data['id']];

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
                    $child_data = $field_data['children'][$field_index];

                    $row[$child_data['name']] = $this->format_field_value(
                        $child_field['type'],
                        $value['value']
                    );

                    $fieldset[$row_index] = $row;
                    $i++;
                }

                $data[$field_data['name']] = $fieldset;
            } else {
                $data[$field_data['name']] = $this->format_field_value(
                    $field_data['type'],
                    $field['value']
                );

                if (
                    $data[$field_data['name']] === false &&
                    $field_data['schema']['type'] !== 'boolean' &&
                    $field_data['conditional']
                ) {
                    $data[$field_data['name']] = null;
                }
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
            }

            return $value;
        } elseif ($type === 'number' || $type === 'starrating') {
            return (float) $value;
        } elseif ($type === 'date') {
            if (is_string($value)) {
                return $value;
            }

            $_value = '';

            if (!empty($value['date'])) {
                $_value .= $value['date'] . ' ';
            }

            if (isset($value['hour'])) {
                $_value .= "{$value['hour']}:{$value['minute']}";

                if (isset($value['ampm'])) {
                    $_value .= ' ' . $value['ampm'];
                }
            }

            return trim($_value);
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
        $uploads = [];

        foreach ($form_data['fields'] as $field_data) {
            if (!$field_data['is_file']) {
                continue;
            }

            $field = $submission['fields'][(int) $field_data['id']];

            $uploads_path =
                wp_upload_dir()['basedir'] . '/ninja-forms/' . $form_data['id'];
            $urls = $field['value'];
            $paths = [];
            foreach ($urls as $url) {
                $basename = basename($url);
                $path = $uploads_path . '/' . $basename;
                if (is_file($path)) {
                    $paths[] = $path;
                }
            }

            if (empty($paths)) {
                continue;
            }

            if ($field_data['is_multi']) {
                $uploads[$field_data['name']] = [
                    'path' => $paths,
                    'is_multi' => true,
                ];
            } else {
                $uploads[$field_data['name']] = [
                    'path' => $paths[0],
                    'is_multi' => false,
                ];
            }
        }

        return $uploads;
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
                case 'number':
                    $constraints = [];
                    if (isset($field['min'])) {
                        $constraints['num_min'] = $field['min'];
                    }

                    if (isset($field['max'])) {
                        $constraints['num_max'] = $field['max'];
                    }

                    if (isset($field['step'])) {
                        $constraints['num_step'] = $field['step'];
                    }

                    $args[] = $constraints;
                    $nf_fields[] = $this->number_field(...$args);
                    break;
                case 'text':
                    $nf_fields[] = $this->text_field(...$args);
                    break;
                case 'textarea':
                    $nf_fields[] = $this->textarea_field(...$args);
                    break;
                case 'email':
                    $nf_fields[] = $this->email_field(...$args);
                    break;
                case 'date':
                    $nf_fields[] = $this->date_field(...$args);
                    break;
                case 'file':
                    $args[] = $field['is_multi'] ?? false;
                    $args[] = $field['filetypes'] ?? '';
                    $nf_fields[] = $this->upload_field(...$args);
                    break;
                case 'hidden':
                    if (isset($field['value'])) {
                        if (is_bool($field['value'])) {
                            $field['value'] = $field['value'] ? '1' : '0';
                        }

                        $args[] = (string) $field['value'];
                        $nf_fields[] = $this->hidden_field(...$args);
                    }

                    break;
                case 'select':
                    $args[] = $field['options'] ?? [];
                    $args[] = $field['is_multi'] ?? false;
                    $nf_fields[] = $this->select_field(...$args);
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
            'order' => count($nf_fields) + 1,
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
            // 'custom_name_attribute' => $name,
            'admin_label' => '',
            'required' => $required ? '1' : '',
            'default' => '',
            'placeholder' => '',
            'container_class' => '',
            'label_pos' => 'default',
        ];
    }

    private function upload_field(
        $order,
        $name,
        $label,
        $required,
        $is_multi,
        $filetypes = ''
    ) {
        $filetypes = preg_replace('/\.(?=[A-Za-z]{2})/', '', $filetypes);

        return array_merge(
            $this->field_template(
                'file_upload',
                $order,
                $name,
                $label,
                $required
            ),
            [
                'upload_types' => $filetypes,
                'upload_multi_count' => $is_multi ? 2 : 1,
            ]
        );
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

    private function select_field(
        $order,
        $name,
        $label,
        $required,
        $options,
        $is_multi
    ) {
        $_options = [];
        for ($i = 0; $i < count($options); $i++) {
            $_options[] = [
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
                'options' => $_options,
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

    private function number_field(
        $order,
        $name,
        $label,
        $required,
        $constraints
    ) {
        return array_merge(
            $this->field_template('number', $order, $name, $label, $required),
            $constraints
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

    private function date_field($order, $name, $label, $required)
    {
        return array_merge(
            $this->field_template('date', $order, $name, $label, $required),
            [
                'date_format' => 'DD/MM/YYYY',
                'date_mode' => 'date_only',
                'date_default' => 1,
                'hours_24' => 0,
                'year_range_start' => '',
                'year_range_end' => '',
                'minute_increment' => 5,
            ]
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
                    'fields-save-toggle' => 'save_all',
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

Integration::setup();
