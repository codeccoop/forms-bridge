<?php

namespace FORMS_BRIDGE\GF;

use FORMS_BRIDGE\Integration as BaseIntegration;
use Exception;
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
     * Inherits prent constructor and hooks submissions to gform_after_submission
     */
    protected function construct(...$args)
    {
        add_action(
            'gform_after_submission',
            function ($entry, $form) {
                $this->do_submission($entry, $form);
            },
            10,
            2
        );

        parent::construct(...$args);
    }

    /**
     * Integration initializer to be fired on wp init.
     */
    protected function init()
    {
    }

    /**
     * Retrives the current form data.
     *
     * @return array Form data.
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
     * Retrives form data by ID.
     *
     * @param int $form_id Form ID.
     *
     * @return array Form data.
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
     * Retrives available forms data.
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
     * Retrives the current submission data.
     *
     * @return array Submission data.
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

        return $this->serialize_submission($submission, $this->form());
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

        return $this->submission_uploads($submission, $this->form());
    }

    /**
     * Serializes gf form data.
     *
     * @param array $form GF form data.
     *
     * @return array Form data.
     */
    public function serialize_form($form)
    {
        return [
            'id' => $form['id'],
            'title' => $form['title'],
            'hooks' => apply_filters(
                'forms_bridge_form_hooks',
                null,
                $form['id']
            ),
            'description' => $form['description'],
            'fields' => array_map(function ($field) {
                return $this->serialize_field($field);
            }, $form['fields']),
        ];
        return $form;
    }

    /**
     * Serializes GF form data field.
     *
     * @param GFField $field Field object instance.
     *
     * @return array Field data.
     */
    private function serialize_field($field)
    {
        switch ($field->type) {
            case 'post_image':
            case 'fileupload':
                $type = $field->multipleFiles ? 'files' : 'file';
                break;
            default:
                $type = $field->type;
                break;
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
            'type' => $type,
            'name' => $name,
            'label' => $field->label,
            'required' => $field->isRequired,
            'options' => $options,
            'inputs' => $inputs,
            'conditional' =>
                is_array($field->conditionalLogic) &&
                $field->conditionalLogic['enabled'],
        ];
    }

    /**
     * Serializes current form submission data.
     *
     * @param array $submission GF form submission.
     * @param array $form Form data.
     *
     * @return array Submission data.
     */
    public function serialize_submission($submission, $form_data)
    {
        $data = [
            'submission_id' => $submission['id'],
        ];

        foreach ($form_data['fields'] as $field) {
            if (
                $field['type'] === 'section' ||
                $field['type'] === 'file' ||
                $field['type'] === 'files' ||
                $field['type'] === 'html'
            ) {
                continue;
            }

            $input_name = $field['name'];
            $inputs = $field['inputs'];

            if (!empty($inputs)) {
                // composed fields
                $names = array_map(function ($input) {
                    return $input['name'];
                }, $inputs);
                if (!empty(array_filter($names))) {
                    // Composed with subfields
                    foreach (array_keys($names) as $i) {
                        if (empty($names[$i])) {
                            continue;
                        }
                        $data[$names[$i]] = rgar(
                            $submission,
                            (string) $inputs[$i]['id']
                        );
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

                    $data[$input_name] = implode(',', $values);
                }
            } else {
                // simple fields
                if ($input_name) {
                    if ($field['type'] === 'consent') {
                        $raw_value = rgar($submission, $field['id'] . '.1');
                    } else {
                        $raw_value = rgar($submission, (string) $field['id']);
                    }
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
     * @param mixed $value Field value.
     * @param GFField $field Field object instance.
     * @param array $input Field input data.
     *
     * @return mixed Formatted value.
     */
    private function format_value($value, $field, $input = null)
    {
        try {
            if ($field['type'] === 'consent') {
                if (isset($input['isHidden']) && $input['isHidden']) {
                    return null;
                }
                return $value;
            }
        } catch (TypeError $e) {
            // do nothing
        } catch (Exception $e) {
            // do nothing
        }

        return $value;
    }

    /**
     * Gets current submission uploaded files.
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
                return $field['type'] === 'file' || $field['type'] === 'files';
            }),
            function ($carry, $field) use ($submission, $private_upload) {
                $paths = rgar($submission, (string) $field['id']);
                if (empty($paths)) {
                    return $carry;
                }

                $paths =
                    $field['type'] === 'files' ? json_decode($paths) : [$paths];
                $paths = array_map(function ($path) use ($private_upload) {
                    if ($private_upload) {
                        $url = parse_url($path);
                        parse_str($url['query'], $query);
                        $path = forms_bridge_attachment_fullpath(
                            $query['forms-bridge-attachment']
                        );
                    }

                    return $path;
                }, $paths);

                $carry[$field['name']] = [
                    'path' => $field['type'] === 'files' ? $paths : $paths[0],
                    'is_multi' => $field['type'] === 'files',
                ];

                return $carry;
            },
            []
        );
    }
}
