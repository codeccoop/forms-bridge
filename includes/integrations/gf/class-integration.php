<?php

namespace WPCT_ERP_FORMS\GF;

use Exception;
use TypeError;
use WPCT_ERP_FORMS\Integration as BaseIntegration;

require_once 'attachments.php';
require_once 'fields-population.php';

class Integration extends BaseIntegration
{
    protected function __construct()
    {
        add_action('gform_after_submission', function ($entry, $form) {
            $this->do_submission($entry, $form);
        }, 10, 2);

        parent::__construct();
    }

	protected function init()
	{
	}

    public function serialize_form($form)
    {
        return [
            'id' => $form['id'],
            'title' => $form['title'],
            'ref' => apply_filters('wpct_erp_forms_form_ref', null, $form['id']),
            'description' => $form['description'],
            'fields' => array_map(function ($field) {
                return $this->serialize_field($field);
            }, $form['fields']),
        ];
        return $form;
    }

    private function serialize_field($field)
    {
        switch ($field->type) {
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
                return ['name' => $input['name'], 'label' => $input['label'], 'id' => $input['id']];
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
            'conditional' => is_array($field->conditionalLogic) && $field->conditionalLogic['enabled'],
        ];
    }


    public function serialize_submission($submission, $form_data)
    {
        $data = [
            'submission_id' => $submission['id']
        ];

        foreach ($form_data['fields'] as $field) {
            if (
                $field['type'] === 'section'
                    || $field['type'] === 'file'
                    || $field['type'] === 'files'
                    || $field['type'] === 'html'
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
                        $data[$names[$i]] = rgar($submission, (string) $inputs[$i]['id']);
                    }
                } else {
                    // Plain composed
                    $values = [];
                    foreach ($inputs as $input) {
                        $value = rgar($submission, (string) $input['id']);
                        if ($input_name && $value) {
                            $value = $this->format_value($value, $field, $input);
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
                    $data[$input_name] = $this->format_value($raw_value, $field);
                }
            }
        }

        return $data;
    }

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

    protected function get_uploads($submission, $form_data)
    {
        $private_upload = wpct_erp_forms_private_upload($form_data['id']);

        return array_reduce(array_filter($form_data['fields'], function ($field) {
            return $field['type'] === 'file' || $field['type'] === 'files';
        }), function ($carry, $field) use ($submission, $private_upload) {
            $paths = rgar($submission, (string) $field['id']);
            if (empty($paths)) {
                return $carry;
            }

            $paths = $field['type'] === 'files' ? json_decode($paths) : [$paths];
            $paths = array_map(function ($path) use ($private_upload) {
                if ($private_upload) {
                    $url = parse_url($path);
                    parse_str($url['query'], $query);
                    $path = wpct_erp_forms_attachment_fullpath($query['erp-forms-attachment']);
                }

                return $path;
            }, $paths);

            $carry[$field['name']] = [
                'path' => $field['type'] === 'files' ? $paths : $paths[0],
                'is_multi' => $field['type'] === 'files'
            ];

            return $carry;
        }, []);
    }
}
