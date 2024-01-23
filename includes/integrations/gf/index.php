<?php

namespace WPCT_ERP_FORMS\Integrations;

use WPCT_ERP_FORMS\Fields\GF\Iban\Field as IbanField;
use WPCT_ERP_FORMS\Integrations\Integration;
use Exception;

require_once 'attachments.php';
require_once 'fields-population.php';

class GF extends Integration
{
    public static $fields = [
        IbanField::class
    ];

    public function register()
    {
        add_action('gform_after_submission', function ($entry, $form) {
            $this->do_submission($entry, $form);
        }, 10, 2);

        add_action('admin_init', function () {
            global $wpct_erp_forms_admin_menu;
            ($wpct_erp_forms_admin_menu->get_settings())->register_field('coord_id', 'wpct_erp_forms_general');
        }, 90);

        add_filter('wpct_erp_forms_general_default', function ($defaults) {
            $defaults['coord_id'] = 1;
            return $defaults;
        });
    }

    public function serialize_form($form)
    {
        return $form;
    }


    public function serialize_submission($entry, $form)
    {
        $submission = $this->get_entry_data($entry, $form);
        $this->add_coord_id($submission);
        return $submission;
    }

    private function get_entry_data($entry, $form)
    {
        $submission = [
            'entry_id' => $entry['id']
        ];

        foreach ($form['fields'] as $field) {
            if ($field->type === 'section') continue;

            $input_name = $field->inputName
                ? $field->inputName
                : ($field->adminLabel
                    ? $field->adminLabel
                    : $field->label);

            $inputs = $field->get_entry_inputs();

            if (is_array($inputs)) {
                // composed fields
                $names = array_map(function ($input) {
                    return $input['name'];
                }, $inputs);
                if (sizeof(array_filter($names, fn ($name) => $name))) {
                    // Composed with subfields
                    for ($i = 0; $i < sizeof($inputs); $i++) {
                        if (empty($names[$i])) continue;
                        $submission[$names[$i]] = rgar($entry, (string) $inputs[$i]['id']);
                    }
                } else {
                    // Plain composed
                    $values = [];
                    foreach ($inputs as $input) {
                        $value = rgar($entry, (string) $input['id']);
                        if ($input_name && $value) {
                            $value = $this->format_value($value, $field, $input);
                            if ($value !== null) $values[] = $value;
                        }
                    }

                    $submission[$input_name] = implode(',', $values);
                }
            } else {
                // simple fields
                if ($input_name) {
                    $raw_value = rgar($entry, (string) $field->id);
                    $submission[$input_name] = $this->format_value($raw_value, $field);
                }
            }
        }

        return $submission;
    }

    private function format_value($value, $field, $input = null)
    {
        try {
            if ($field->type === 'fileupload' && $value && is_string($value)) {
                return implode(',', json_decode($value));
            } else if ($field->type === 'consent') {
                if (isset($input['isHidden']) && $input['isHidden']) return null;
                return $value;
            }
        } catch (Exception $e) {
            // do nothing
        }

        return $value;
    }

    private function add_coord_id(&$submission)
    {
        if (!isset($submission['company_id']) || $submission['company_id']) {
            $settings = get_option('wpct_erp_forms_general', []);
            if (!isset($settings['coord_id'])) return;
            $submission['company_id'] = $settings['coord_id'];
        }
    }
}
