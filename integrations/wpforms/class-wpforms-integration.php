<?php

namespace FORMS_BRIDGE\WPFORMS;

use FBAPI;
use FORMS_BRIDGE\Forms_Bridge;
use FORMS_BRIDGE\Integration as BaseIntegration;
use WP_Post;
use WP_Query;
use WPForms_Field_File_Upload;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * WPForms integration.
 */
class Integration extends BaseIntegration
{
    public const name = 'wpforms';

    public const title = 'WP Forms';

    /**
     * Handles the current submission data.
     *
     * @var array|null
     */
    private static $submission = null;

    /**
     * Binds process complete hook to the do_submission routine.
     */
    protected function init()
    {
        add_action(
            'wpforms_process_complete',
            function ($fields, $entry, $form_data, $entry_id) {
                $entry['fields'] = $fields;
                $entry['entry_id'] = $entry_id;
                self::$submission = $entry;

                Forms_Bridge::do_submission();
            },
            10,
            4
        );
    }

    /**
     * Retrives the current WPForms_Form_Handler's data.
     *
     * @return array Form data.
     */
    public function form()
    {
        $form_id = !empty($_POST['wpforms']['id'])
            ? absint($_POST['wpforms']['id'])
            : 0;

        if (!$form_id) {
            return;
        }

        $form = wpforms()->obj('form')->get($form_id);

        if (!$form) {
            return;
        }

        return $this->serialize_form($form);
    }

    /**
     * Retrives a WPForms_Form_Handler's data by ID.
     *
     * @param int $form_id ID of the form.
     *
     * @return array.
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
     * Retrives available form instances' data.
     *
     * @return array Collection of forms data.
     */
    public function forms()
    {
        $forms = array_filter((array) wpforms()->obj('form')->get());
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
     */
    public function create_form($data)
    {
        $form_title = esc_html($data['title']);
        $title_query = new WP_Query([
            'post_type' => 'wpforms',
            'title' => $form_title,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'no_found_rows' => true,
        ]);
        $title_exists = $title_query->post_count > 0;

        add_filter(
            'wpforms_create_form_args',
            function ($args, $create_data) use ($data) {
                if ($create_data['template'] === 'forms-bridge') {
                    $args['post_content'] = $this->encode_form_data($data);
                }
                return $args;
            },
            99,
            2
        );

        $form_id = wpforms()
            ->obj('form')
            ->add(
                esc_html($data['title']),
                [],
                [
                    'template' => 'forms-bridge',
                    'category' => 'all',
                    'subcategory' => 'all',
                ]
            );

        if ($title_exists) {
            # $form_title = $form_title . ' (ID #' . $form_id . ')';
            remove_action('post_updated', 'wp_save_post_revision');
            wp_update_post([
                'ID' => $form_id,
                'post_title' => $form_title . ' (ID #' . $form_id . ')',
            ]);
            add_action('post_updated', 'wp_save_post_revision');
        }

        $form = wpforms()->obj('form')->get($form_id);
        $form_data = wpforms_decode($form->post_content);
        $form_data['id'] = $form_id;
        $form_data['settings']['form_title'] = $form_title;

        wpforms()
            ->obj('form')
            ->update($form_id, $form_data, ['context' => 'save_form']);

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
        $post = wp_delete_post($form_id);
        return boolval($post->ID ?? false);
    }

    public function submission_id()
    {
        $submission = $this->submission(true);
        if ($submission) {
            return $submission['entry_id'];
        }
    }

    /**
     * Retrives the current form submission data.
     *
     * @param boolean $raw Control if the submission is serialized before exit.
     *
     * @return array Submission data.
     */
    public function submission($raw = false)
    {
        $form = $this->form();

        if (!$form) {
            return;
        }

        if (empty(self::$submission)) {
            return;
        } elseif ($raw) {
            return self::$submission;
        }

        return $this->serialize_submission(self::$submission, $form);
    }

    /**
     * Retrives the current submission uploaded files.
     *
     * @return array Uploaded files data.
     */
    public function uploads()
    {
        $submission = self::$submission;
        if (!$submission) {
            return null;
        }

        return $this->submission_uploads($submission, $this->form());
    }

    /**
     * Serializes a wp form post instance as array data.
     *
     * @param WP_Post $form Form post instance.
     *
     * @return array
     */
    public function serialize_form($form)
    {
        $data =
            $form instanceof WP_Post
                ? wpforms_decode($form->post_content)
                : $form;

        $form_id = isset($data['id']) ? (int) $data['id'] : $form->ID;

        return apply_filters(
            'forms_bridge_form_data',
            [
                '_id' => 'wpforms:' . $form_id,
                'id' => $form_id,
                'title' => $data['settings']['form_title'] ?? '',
                'bridges' => FBAPI::get_form_bridges($form_id, 'wpforms'),
                'fields' => array_reduce(
                    $data['fields'],
                    function ($fields, $field) use ($data) {
                        $field = $this->serialize_field(
                            $field,
                            $fields,
                            $data['fields']
                        );

                        if ($field) {
                            $fields[] = $field;
                        }

                        return $fields;
                    },
                    []
                ),
            ],
            $data,
            'wpforms'
        );
    }

    /**
     * Serializes a field as array data.
     *
     * @param array $field Field data.
     * @param array $form_data Form data.
     *
     * @return array
     */
    private function serialize_field($field, $fields = [], $all_fields = [])
    {
        if (
            in_array($field['type'], [
                'submit',
                'pagebreak',
                'layout',
                'captcha',
                'content',
                'entry-preview',
                'html',
                'divider',
            ])
        ) {
            return;
        }

        $repeaters = array_filter($fields, static function ($field) {
            return $field['type'] === 'repeater';
        });

        $fields_in_repeater = array_reduce(
            $repeaters,
            static function ($ids, $repeater) {
                foreach ($repeater['children'] as $child) {
                    $ids[] = $child['id'];
                }

                return $ids;
            },
            []
        );

        $children = [];
        if ($field['type'] === 'repeater') {
            foreach ($field['columns'] as $column) {
                foreach ($column['fields'] as $field_id) {
                    $children[] = $field_id;
                }
            }

            $children = array_map(
                function ($field) {
                    return $this->serialize_field($field);
                },
                array_filter($all_fields, static function ($field) use (
                    $children
                ) {
                    return in_array($field['id'], $children);
                })
            );
        } else {
            if (in_array($field['id'], $fields_in_repeater)) {
                return;
            }
        }

        $format = $field['date_format'] ?? '';
        if ($format) {
            $format =
                [
                    'd/m/Y' => 'dd/mm/yyyy',
                    'm/d/Y' => 'mm/dd/yyyy',
                ][$format] ?? '';
        }

        return apply_filters(
            'forms_bridge_form_field_data',
            [
                'id' => (int) ($field['id'] ?? 0),
                'type' => $field['type'],
                'name' => $field['label'] ?? '',
                'label' => $field['label'] ?? '',
                'required' => ($field['required'] ?? '') === '1',
                'options' => isset($field['choices']) ? $field['choices'] : [],
                'is_file' => $field['type'] === 'file-upload',
                'is_multi' => $this->is_multi_field($field),
                'conditional' => false,
                'format' => $format,
                'children' => array_values($children),
                'schema' => $this->field_value_schema($field, $children),
            ],
            $field,
            'wpforms'
        );
    }

    /**
     * Checks if a filed is multi value field.
     *
     * @param array Target field instance.
     *
     * @return boolean
     */
    private function is_multi_field($field)
    {
        if ($field['type'] === 'checkbox' || $field['type'] === 'repeater') {
            return true;
        }

        if ($field['type'] === 'select' && ($field['multiple'] ?? false)) {
            return true;
        }

        if (
            $field['type'] === 'file-upload' &&
            ($field['max_file_number'] ?? 1) !== '1'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Gets the field value JSON schema.
     *
     * @param array $field Field instance.
     * @param array $children Children fields.
     *
     * @return array JSON schema of the value of the field.
     */
    private function field_value_schema($field, $children = [])
    {
        switch ($field['type']) {
            case 'name':
            case 'email':
            case 'text':
            case 'textarea':
            case 'payment-total':
            case 'payment-single':
            case 'radio':
            case 'password':
            case 'url':
                return ['type' => 'string'];
            case 'number-slider':
            case 'number':
                return ['type' => 'number'];
            case 'payment-select':
            case 'payment-multiple':
            case 'payment-checkbox':
            case 'select':
                if ($field['multiple'] ?? false) {
                    $items = [];
                    for ($i = 0; $i < count($field['choices']); $i++) {
                        $items[] = ['type' => 'string'];
                    }

                    return [
                        'type' => 'array',
                        'items' => $items,
                        'additionalItems' => false,
                    ];
                }

                return ['type' => 'string'];
            case 'checkbox':
                $items = [];
                for ($i = 0; $i < count($field['choices']); $i++) {
                    $items[] = ['type' => 'string'];
                }

                return [
                    'type' => 'array',
                    'items' => $items,
                    'additionalItems' => false,
                ];
            case 'date-time':
                if ($field['format'] === 'date-time') {
                    return [
                        'type' => 'object',
                        'properties' => [
                            'date' => ['type' => 'string'],
                            'time' => ['type' => 'string'],
                        ],
                    ];
                }

                return ['type' => 'string'];
            case 'address':
                $properties = [
                    'address1' => ['type' => 'string'],
                    'city' => ['type' => 'string'],
                    'state' => ['type' => 'string'],
                ];

                if (($field['address2_hide'] ?? '0') !== '1') {
                    $properties['address2'] = ['type' => 'string'];
                }

                if (($field['postal_hide'] ?? '0') !== '1') {
                    $properties['postal'] = ['type' => 'string'];
                }

                if (
                    ($field['country_hide'] ?? '0') !== '1' &&
                    $field['scheme'] !== 'us'
                ) {
                    $properties['country'] = ['type' => 'string'];
                }

                return [
                    'type' => 'object',
                    'properties' => $properties,
                ];
            case 'file-upload':
                return;
            case 'repeater':
                $properties = array_reduce(
                    $children,
                    function ($props, $field) {
                        $props[$field['label']] = $field['schema'];
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
            default:
                return ['type' => 'string'];
        }
    }

    /**
     * Serializes the form's submission data.
     *
     * @param array $submission Submission data.
     * @param array $form Form data.
     *
     * @return array
     */
    public function serialize_submission($submission, $form_data)
    {
        $data = [];
        $repeaters = array_filter($form_data['fields'], static function (
            $field
        ) {
            return $field['type'] === 'repeater';
        });

        $fields_in_repeaters = array_reduce(
            $repeaters,
            static function ($ids, $repeater) {
                foreach ($repeater['children'] as $child) {
                    $ids[$child['id']] = [];
                }

                return $ids;
            },
            []
        );

        foreach ($submission['fields'] as $field) {
            if ($field['type'] === 'file-upload') {
                continue;
            }

            $i = array_search(
                $field['name'],
                array_column($form_data['fields'], 'name')
            );
            $field_data = $form_data['fields'][$i];

            $field['id'] = preg_replace('/_\d+$/', '', $field['id']);
            if (isset($fields_in_repeaters[$field['id']])) {
                $fields_in_repeaters[$field['id']][] = $field;
            } else {
                $value = $this->format_value($field, $field_data);
                $data[$field_data['name']] = $value;
            }
        }

        foreach ($repeaters as $repeater) {
            $repeater_data = [];
            foreach ($repeater['children'] as $child) {
                foreach ($fields_in_repeaters as $id => $fields) {
                    if ($id == $child['id']) {
                        break;
                    }
                }

                for ($i = 0; $i < count($fields); $i++) {
                    $field = $fields[$i];
                    $value = $this->format_value($field, $child);
                    $datum =
                        count($repeater_data) > $i ? $repeater_data[$i] : [];
                    $datum[$child['name']] = $value;
                    $repeater_data[$i] = $datum;
                }
            }

            $data[$repeater['name']] = $repeater_data;
        }

        return $data;
    }

    private function format_value($field, $field_data)
    {
        if (strstr($field['type'], 'payment')) {
            $field['value'] = html_entity_decode($field['value']);
        }

        if ($field_data['type'] === 'hidden') {
            $number_val = (float) $field['value'];
            if (strval($number_val) === $field['value']) {
                return $number_val;
            }
        }

        if (
            $field_data['type'] === 'number' ||
            $field_data['type'] === 'number-slider'
        ) {
            if (isset($field['amount'])) {
                $value = (float) $field['amount'];
                if (isset($field['currency'])) {
                    $value .= ' ' . $field['currency'];
                }

                return $value;
            } else {
                return (float) preg_replace('/[^0-9\.,]/', '', $field['value']);
            }
        }

        if (
            $field_data['type'] === 'select' ||
            $field_data['type'] === 'checkbox'
        ) {
            if ($field_data['is_multi']) {
                return array_map(function ($value) {
                    return trim($value);
                }, explode("\n", $field['value']));
            }
        }

        if ($field_data['type'] === 'address') {
            $post_values = $_POST['wpforms']['fields'][$field['id']];
            $field_values = [];
            foreach (array_keys($field_data['schema']['properties']) as $prop) {
                $field_values[$prop] = $post_values[$prop] ?? '';
            }

            return $field_values;
        }

        if ($field_data['type'] === 'date-time') {
            if ($field_data['schema']['type'] === 'object') {
                $post_values = $_POST['wpforms']['fields'][$field['id']];
                return [
                    'date' => $post_values['date'],
                    'time' => $post_values['time'],
                ];
            }
        }

        return (string) $field['value'];
    }

    /**
     * Gets submission uploaded files.
     *
     * @param object $submission Submission data.
     * @param array $form_data Form data.
     *
     * @return array Uploaded files data.
     */
    protected function submission_uploads($submission, $form_data)
    {
        $form_fields = wpforms_get_form_fields((int) $form_data['id'], [
            'file-upload',
        ]);

        if (empty($form_fields)) {
            return [];
        }

        $fields = [];
        foreach ($form_fields as $form_field) {
            foreach ($submission['fields'] as $submission_field) {
                if ($submission_field['id'] == $form_field['id']) {
                    $fields[] = $submission_field;
                }
            }
        }

        $uploads = [];
        foreach ($fields as $field) {
            if (empty($field['value_raw'])) {
                continue;
            }

            $is_multi = count($field['value_raw'] ?: []) > 1;
            $paths = WPForms_Field_File_Upload::get_entry_field_file_paths(
                $form_data['id'],
                $field
            );
            $uploads[$field['name']] = [
                'path' => $is_multi ? $paths : $paths[0],
                'is_multi' => $is_multi,
            ];
        }

        return $uploads;
    }

    /**
     * Formats the bridge's form data to be used as the post_content of a wpform post
     * type and encode it as json.
     *
     * @param array $data Bridge's template form data.
     *
     * @return string Encoded and decorated form data.
     */
    private function encode_form_data($data)
    {
        $wp_fields = [];
        for ($i = 0; $i < count($data['fields']); $i++) {
            $id = $i + 1;
            $field = $data['fields'][$i];

            $args = [$id, $field['name'], $field['required'] ?? false];
            switch ($field['type']) {
                case 'textarea':
                    $wp_fields[strval($id)] = $this->textarea_field(...$args);
                    break;
                case 'hidden':
                    if (isset($field['value'])) {
                        if (is_bool($field['value'])) {
                            $field['value'] = $field['value'] ? '1' : '0';
                        }

                        $args[] = (string) $field['value'];
                        $wp_fields[strval($id)] = $this->hidden_field(...$args);
                    }

                    break;
                case 'options':
                    $args[] = $field['options'] ?? [];
                    $args[] = $field['is_multi'] ?? false;
                    $wp_fields[strval($id)] = $this->options_field(...$args);
                    break;
                case 'file':
                    $args[] = $field['filetypes'] ?? '';
                    $wp_fields[strval($id)] = $this->file_field(...$args);
                    break;
                case 'date':
                    $wp_fields[strval($id)] = $this->date_field(...$args);
                    break;
                case 'text':
                    $wp_fields[strval($id)] = $this->text_field(...$args);
                    break;
                case 'number':
                    $constraints = [
                        'default_value' => floatval($field['default'] ?? 0),
                        'min' => $field['min'] ?? '',
                        'max' => $field['max'] ?? '',
                        'step' => $field['step'] ?? '1',
                    ];

                    $args[] = $constraints;
                    $wp_fields[strval($id)] = $this->number_field(...$args);
                    break;
                // case 'url':
                // case 'email':
                // case 'number':
                default:
                    $wp_fields[strval($id)] = $this->field_template(
                        $field['type'],
                        ...$args
                    );
            }
        }

        return wpforms_encode([
            'fields' => $wp_fields,
            'field_id' => $id + 1,
            'settings' => [
                'form_desc' => '',
                'submit_text' => esc_html__('Submit', 'forms-bridge'),
                'submit_text_processing' => esc_html__(
                    'Sending...',
                    'forms-bridge'
                ),
                'antispam_v3' => '1',
                'notification_enable' => '1',
                'notifications' => [
                    '1' => [
                        'email' => '{admin_email}',
                        'replyto' => '',
                        'message' => '{all_fields}',
                    ],
                ],
                'confirmations' => [
                    '1' => [
                        'type' => 'message',
                        'message' => esc_html__(
                            'Thanks for contacting us! We will be in touch with you shortly.',
                            'forms-bridge'
                        ),
                        'message_scroll' => '1',
                    ],
                ],
                'ajax_submit' => '1',
            ],
            'meta' => ['template' => 'forms-bridge'],
        ]);
    }

    /**
     * Returns a default field array data. Used as template for the field creation methods.
     *
     * @param string $type Field type.
     * @param int $id Field id.
     * @param string $label Field label.
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function field_template($type, $id, $label, $required)
    {
        return [
            'id' => (string) $id,
            'type' => $type,
            'label' => esc_html($label),
            'required' => $required ? '1' : '0',
            'size' => 'medium',
            'description' => '',
            'placeholder' => '',
            'css' => '',
        ];
    }

    /**
     * Returns a valid text field data.
     *
     * @param int $id Field id.
     * @param string $name Field name (label).
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function text_field($id, $name, $required)
    {
        return array_merge(
            $this->field_template('text', $id, $name, $required),
            [
                'limit_count' => '1',
                'limit_mode' => 'characters',
            ]
        );
    }

    /**
     * Returns a valid number field data.
     *
     * @param int $id Field id.
     * @param string $name Field name (label).
     * @param boolean $required Is field required.
     * @param array $constraints Field constraints.
     *
     * @return array
     */
    private function number_field($id, $name, $required, $constraints)
    {
        return array_merge(
            $this->field_template('number-slider', $id, $name, $required),
            $constraints
        );
    }

    /**
     * Returns a valid text field data.
     *
     * @param int $id Field id.
     * @param string $name Field name (label).
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function date_field($id, $name, $required)
    {
        return array_merge(
            $this->field_template('date-time', $id, $name, $required),
            [
                'format' => 'date',
                'date_type' => 'datepicker',
                'date_format' => 'd/m/Y',
            ]
        );
    }

    /**
     * Returns a valid textarea field data.
     *
     * @param int $id Field id.
     * @param string $name Field name (label).
     * @param boolean $required Is field required.
     *
     * @return array
     */
    private function textarea_field($id, $name, $required)
    {
        return array_merge(
            $this->field_template('textarea', $id, $name, $required),
            [
                'limit_count' => '1',
                'limit_mode' => 'characters',
            ]
        );
    }

    /**
     * Returns a valid multi options field data, as a select field if is single, as
     * a checkbox field if is multiple.
     *
     * @param int $id Field id.
     * @param string $name Field name (label).
     * @param boolean $required Is field required.
     * @param array Options data.
     * @param boolean Is field multi value.
     *
     * @return array
     */
    private function options_field($id, $name, $required, $options, $is_multi)
    {
        $choices = array_map(function ($opt) {
            return [
                'label' => esc_html($opt['label']),
                'value' => sanitize_text_field($opt['value']),
                'image' => '',
                'icon' => '',
                'icon_style' => 'regular',
            ];
        }, $options);

        if ($is_multi) {
            return array_merge(
                $this->field_template('checkbox', $id, $name, $required),
                [
                    'choices' => $choices,
                    'choices_images_style' => 'modern',
                    'choices_icon_color' => '#066aab',
                    'choices_icon_size' => 'large',
                    'choices_icon_style' => 'default',
                    'choices_limit' => '',
                    'dynamic_choices' => '',
                ]
            );
        } else {
            return array_merge(
                $this->field_template('select', $id, $name, $required),
                [
                    'choices' => $choices,
                    'dynamic_choices' => '',
                    'style' => 'classic',
                ]
            );
        }
    }

    /**
     * Returns a valid hidden field data.
     *
     * @param int $id Field id.
     * @param string $name Field name (label).
     * @param boolean $required Is field required.
     * @param string $value Field's default value.
     *
     * @return array
     */
    private function hidden_field($id, $name, $required, $value)
    {
        $field = array_merge(
            $this->field_template('hidden', $id, $name, $required),
            [
                'label_hide' => '1',
                'label_disable' => '1',
                'default_value' => $value,
            ]
        );

        unset($field['description']);
        unset($field['required']);
        unset($field['placeholder']);

        return $field;
    }

    /**
     * Returns a valid file-upload field data.
     *
     * @param int $id Field id.
     * @param string $name Field name (label).
     * @param boolean $required Is field required.
     * @param string $filetypes String with allowed file extensions separated by commas.
     *
     * @return array
     */
    private function file_field($id, $name, $required, $filetypes)
    {
        return array_merge(
            $this->field_template('file-upload', $id, $name, $required),
            [
                'max_size' => '',
                'max_file_number' => '1',
                'style' => 'modern',
                'extensions' => $filetypes,
            ]
        );
    }
}

Integration::setup();
