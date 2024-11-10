<?php

namespace WPCT_ERP_FORMS\GF;

use Exception;
use TypeError;
use WPCT_ERP_FORMS\Integration as BaseIntegration;
use GFAPI;
use GFCommon;
use GFFormDisplay;
use GFFormsModel;

require_once 'attachments.php';
require_once 'fields-population.php';

/**
 * GravityForms integration.
 *
 * @since 1.0.0
 */
class Integration extends BaseIntegration
{
    /**
     * Inherit prent constructor and hooks submissions to gform_after_submission
     *
     * @since 0.0.1
     */
    protected function __construct()
    {
        add_action(
            'gform_after_submission',
            function ($entry, $form) {
                $this->do_submission($entry, $form);
            },
            10,
            2
        );

        parent::__construct();
    }

    /**
     * Integration initializer to be fired on wp init.
     *
     * @since 0.0.1
     */
    protected function init()
    {
    }

    /**
     * Retrive the current form data.
     *
     * @return array $form_data Form data.
     */
    public function get_form()
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
     * Retrive form data by ID.
     *
     * @since 3.0.0
     *
     * @param int $form_id Form ID.
     * @return array $form_data Form data.
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
     * Retrive available forms data.
     *
     * @since 3.0.0
     *
     * @return array $forms Collection of form data array representations.
     */
    public function get_forms()
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
     * Retrive the current submission data.
     *
     * @since 3.0.0
     *
     * @return array $submission Submission data.
     */
    public function get_submission()
    {
        $form_data = $this->get_form();
        if (!$form_data) {
            return null;
        }

        $submission = GFFormsModel::get_current_lead(
            GFAPI::get_form($form_data['id'])
        );
        if (!$submission) {
            return null;
        }

        return $this->serialize_submission($submission, $this->get_form());
    }

    /**
     * Retrive the current submission uploaded files.
     *
     * @since 3.0.0
     *
     * @return array $files Collection of uploaded files.
     */
    public function get_uploads()
    {
        $form_data = $this->get_form();
        if (!$form_data) {
            return null;
        }

        $submission = GFFormsModel::get_current_lead(
            GFAPI::get_form($form_data['id'])
        );
        if (!$submission) {
            return null;
        }

        return $this->submission_uploads($submission, $this->get_form());
    }

    /**
     * Serialize gf form data.
     *
     * @since 1.0.0
     *
     * @param array $form GF form data.
     * @return array $form_data Form data.
     */
    public function serialize_form($form)
    {
        return [
            'id' => $form['id'],
            'title' => $form['title'],
            'hooks' => apply_filters(
                'wpct_erp_forms_form_hooks',
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
     * Serialize GF form data field.
     *
     * @since 1.0.0
     *
     * @param object GFField instance.
     * @param array From data.
     * @return array $field_data Field data.
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
     * Serialize current form submission data.
     *
     * @since 1.0.0
     *
     * @param array $submission GF form lead.
     * @param array @form Form data.
     * @return array $submission_data Submission data.
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
     * Format field values with noop fallback.
     *
     * @since 1.0.0
     *
     * @param any $value Field value.
     * @param object $field GFField instance.
     * @param array $input GFField input data.
     * @return any $value Formatted value.
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
     * Get current submission uploaded files.
     *
     * @since 1.0.0
     *
     * @param array $submission GF lead data.
     * @param array $form_data Form data.
     * @return array $uploads Uploaded files data.
     */
    protected function submission_uploads($submission, $form_data)
    {
        $private_upload = wpct_erp_forms_private_upload($form_data['id']);

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
                        $path = wpct_erp_forms_attachment_fullpath(
                            $query['erp-forms-attachment']
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
