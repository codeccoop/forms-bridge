<?php

namespace FORMS_BRIDGE\WPFORMS;

use FORMS_BRIDGE\Integration as BaseIntegration;
use WP_Post;
use WP_Query;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * WPForms integration.
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
                $this->do_submission();
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

    /**
     * Retrives the current form submission data.
     *
     * @return array Submission data.
     */
    public function submission()
    {
        $form = $this->form();
        if (!$form) {
            return;
        }

        if (empty(self::$submission)) {
            return;
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
        $submission = $this->submission();
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

        return [
            '_id' => 'wpforms:' . $form_id,
            'id' => $form_id,
            'title' => $data['settings']['form_title'] ?? '',
            'bridges' => apply_filters(
                'forms_bridge_bridges',
                [],
                'wpforms:' . $form_id
            ),
            'fields' => array_values(
                array_filter(
                    array_map(function ($field) {
                        return $this->serialize_field($field);
                    }, $data['fields'] ?? [])
                )
            ),
        ];
    }

    /**
     * Serializes a field as array data.
     *
     * @param array $field Field data.
     * @param array $form_data Form data.
     *
     * @return array
     */
    private function serialize_field($field)
    {
        // $type = $this->norm_field_type($field['type']);
        if (in_array($field['type'], ['submit'])) {
            return;
        }

        return [
            'id' => (int) ($field['id'] ?? 0),
            'type' => $field['type'],
            'name' => $field['label'] ?? '',
            'label' => $field['label'] ?? '',
            'required' =>
                isset($field['required']) && $field['required'] === '1',
            'options' => isset($field['choices']) ? $field['choices'] : [],
            'is_file' => false, // $field['type'] === 'file',
            'is_multi' =>
                strstr($field['type'], 'checkbox') ||
                ($field['type'] === 'select' &&
                    ($field['multiple'] ?? null) === '1') ||
                ($field['type'] === 'file-upload' &&
                    ($field['max_file_number'] ?? '1') !== '1'),
            'conditional' => false,
        ];
    }

    // private function norm_field_type($type)
    // {
    //     switch ($type) {
    //         case 'name':
    //         case 'email':
    //         case 'textarea':
    //         case 'payment-total':
    //         case 'payment-single':
    //             return 'text';
    //         case 'number-slider':
    //         case 'numbers':
    //             return 'number';
    //         case 'payment-select':
    //         case 'payment-multiple':
    //         case 'payment-checkbox':
    //         case 'select':
    //         case 'radio':
    //         case 'checkbox':
    //             return 'options';
    //         default:
    //             return $type;
    //     }
    // }

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

        foreach ($submission['fields'] as $field) {
            $i = array_search(
                $field['name'],
                array_column($form_data['fields'], 'name')
            );
            $field_data = $form_data['fields'][$i];

            if ($field_data['type'] === 'file') {
                continue;
            }

            if (strstr($field['type'], 'payment')) {
                $field['value'] = html_entity_decode($field['value']);
            }

            if ($field_data['type'] === 'hidden') {
                $number_val = (float) $field['value'];
                if (strval($number_val) === $field['value']) {
                    $data[$field_data['name']] = $number_val;
                } else {
                    $data[$field_data['name']] = $field['value'];
                }
            } elseif ($field_data['type'] === 'number') {
                if (isset($field['amount'])) {
                    $data[$field_data['name']] = (float) $field['amount'];
                    if (isset($field['currency'])) {
                        $data[$field_data['name']] .= ' ' . $field['currency'];
                    }
                } else {
                    $data[$field_data['name']] = (float) preg_replace(
                        '/[^0-9\.,]/',
                        '',
                        $field['value']
                    );
                }
            } elseif ($field_data['type'] === 'options') {
                if ($field_data['is_multi']) {
                    $data[$field_data['name']] = array_map(function ($value) {
                        return trim($value);
                    }, explode("\n", $field['value']));
                } else {
                    $data[$field_data['name']] = $field['value'];
                }
            } else {
                $data[$field_data['name']] = $field['value'];
            }
        }

        return $data;
    }

    /**
     * Gets submission uploaded files.
     *
     * @param object $submission Submission data.
     * @param array $form_data Form data.
     *
     * @return array Uploaded files data.
     *
     * @todo Adapt to premium version with available upload field.
     */
    protected function submission_uploads($submission, $form_data)
    {
        $fields = wpforms_get_form_fields((int) $form_data['id'], [
            'file-upload',
        ]);

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (empty($fields) || empty($_FILES)) {
            return [];
        }

        // Get $_FILES keys generated by WPForms only.
        $files_keys = preg_filter(
            '/^/',
            'wpforms_' . $form_data['id'] . '_',
            array_keys($fields)
        );

        // Filter uploads without errors. Individual errors are handled by WPForms_Field_File_Upload class.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $files = wp_list_filter(wp_array_slice_assoc($_FILES, $files_keys), [
            'error' => 0,
        ]);

        $uploads = [];
        foreach ($files as $file) {
            if (empty($file)) {
                continue;
            }

            $is_multi = sizeof($file) > 1;
            $uploads[$file['name']] = [
                'path' => $is_multi ? $file : $file[0],
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
                    $args[] = $field['value'] ?? '';
                    $wp_fields[strval($id)] = $this->hidden_field(...$args);
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
                case 'text':
                    $wp_fields[strval($id)] = $this->text_field(...$args);
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
