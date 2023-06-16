<?php

/**
 * Gent gform entry and form objects and parse it to a form values array
 */
function wpct_forms_ce_parse_form_entry($entry, $form)
{
    $form_vals = array(
        'entry_id' => $entry['id']
    );

    foreach ($form['fields'] as $field) {
        if ($field->type === 'consent') continue;

        $input_name = $field->inputName ? $field->inputName : $field->label;
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
                        $values[] = $value;
                    }
                }

                $form_vals[$input_name] = implode(',', $values);
            }
        } else {
            // simple fields
            if ($input_name) {
                $form_vals[$input_name] = rgar($entry, (string) $field->id);
            }
        }
    }

    return $form_vals;
}

function wpct_forms_ce_add_cord_id($form_values)
{
    if (!isset($form_values['odoo_company_id']) || !$form_values['odoo_company_id']) {
        $form_values['odoo_company_id'] = wpct_forms_ce_option_getter('wpct_forms_ce_general', 'coord_id');
    }
    return $form_values;
}

/**
 * Remove empty fields from form submission
 */
function wpct_forms_ce_cleanup_empties($form_vals)
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
function wpct_forms_ce_get_submission_payload($form_vals)
{
    $payload = array(
        'name' => $form_vals['source_xml_id'] . ' submission: ' . $form_vals['entry_id'],
        'metadata' => array()
    );

    foreach ($form_vals as $key => $val) {
        if ($key == 'odoo_company_id') {
            $payload['company_id'] = (int) $val;
        } elseif ($key == 'email_from') {
            $payload[$key] = $val;
        } elseif ($key === 'source_xml_id') {
            $payload['source_xml_id'] = $val;
        }

        $payload['metadata'][] = array(
            'key' => $key,
            'value' => $val
        );
    }

    return $payload;
}


/**
 * Pipe form submission transformations to get the submission post payload
 */
function wpct_forms_ce_prepare_submission($form_vals)
{
    $form_vals = wpct_forms_ce_add_cord_id($form_vals);
    $form_vals = wpct_forms_ce_cleanup_empties($form_vals);
    return wpct_forms_ce_get_submission_payload($form_vals);
}

add_filter('wpct_forms_ce_prepare_submission', 'wpct_forms_ce_prepare_submission', 10, 2);
