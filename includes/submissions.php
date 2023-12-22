<?php

/**
 * Gent gform entry and form objects and parse it to a form values array
 */
function wpct_crm_forms_parse_form_entry($entry, $form)
{
    $form_vals = [
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
                    $form_vals[$names[$i]] = rgar($entry, (string) $inputs[$i]['id']);
                }
            } else {
                // Plain composed
                $values = [];
                foreach ($inputs as $input) {
                    $value = rgar($entry, (string) $input['id']);
                    if ($input_name && $value) {
                        $value = wpct_crm_forms_format_value($value, $field, $input);
                        if ($value !== null) $values[] = $value;
                    }
                }

                $form_vals[$input_name] = implode(',', $values);
            }
        } else {
            // simple fields
            if ($input_name) {
                $raw_value = rgar($entry, (string) $field->id);
                $form_vals[$input_name] = wpct_crm_forms_format_value($raw_value, $field);
            }
        }
    }

    return $form_vals;
}

function wpct_crm_forms_format_value($value, $field, $input = null)
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

function wpct_crm_forms_add_cord_id($form_values)
{
    if (!isset($form_values['company_id']) || !$form_values['company_id']) {
        $form_values['company_id'] = wpct_crm_forms_option_getter('wpct_crm_forms_general', 'coord_id');
    }

    return $form_values;
}

/**
 * Remove empty fields from form submission
 */
function wpct_crm_forms_cleanup_empties($form_vals)
{
    foreach ($form_vals as $key => $val) {
        if (empty($val)) {
            unset($form_vals[$key]);
        }
    }

    return $form_vals;
}


/**
 * Transform form submission array into a payload data structure
 */
function wpct_crm_forms_get_submission_payload($form_vals)
{
    $payload = [
        'name' => $form_vals['source_xml_id'] . ' submission: ' . $form_vals['entry_id'],
        'metadata' => []
    ];

    foreach ($form_vals as $key => $val) {
        if ($key == 'company_id') {
            $payload['company_id'] = (int) $val;
        } elseif ($key == 'email_from') {
            $payload[$key] = $val;
        } elseif ($key === 'source_xml_id') {
            $payload['source_xml_id'] = $val;
        }

        $payload['metadata'][] = [
            'key' => $key,
            'value' => $val
        ];
    }

    return $payload;
}


/**
 * Pipe form submission transformations to get the submission post payload
 */
add_filter('wpct_crm_forms_prepare_submission', 'wpct_crm_forms_prepare_submission', 10, 2);
function wpct_crm_forms_prepare_submission($form_vals)
{
    $form_vals = wpct_crm_forms_add_cord_id($form_vals);
    $form_vals = wpct_crm_forms_cleanup_empties($form_vals);
    return wpct_crm_forms_get_submission_payload($form_vals);
}
