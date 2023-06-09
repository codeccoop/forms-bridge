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
        $inputs = $field->get_entry_inputs();
        if (is_array($inputs)) {
            // composed fields
            foreach ($inputs as $input) {
                $input_code = $field->inputName;
                if ($input_code) {
                    $form_vals[$input_code] = rgar($entry, (string) $input['id']);
                }
            }
        } else {
            // simple fields
            $input_code = $field->inputName;
            if ($input_code) {
                $form_vals[$input_code] = rgar($entry, (string) $field->id);
            }
        }
    }

    return $form_vals;
}

function wpct_forms_ce_add_cord_id($form_values)
{
    $form_values['odoo_company_id'] = wpct_forms_ce_option_getter('wpct_forms_ce_general', 'coord_id');
    return $form_values;
}

/**
 * Get forms ce actions map and map form actions values to ids
 */
function wpct_forms_ce_actions_map($form_values)
{
    if (isset($form_values['tag_ids'])) {
        $ids = [];
        $actions = explode(", ", $form_values['tag_ids']);
        foreach ($actions as $action) {
            $ids[] = (int) wpct_forms_ce_option_getter('wpct_forms_ce_actions', $action);
        }
        $form_values['tag_ids'] = $ids;
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
        if ($key == 'company_id') {
            $payload[$key] = (int) $val;
        } elseif ($key == 'email_from') {
            $payload[$key] = $val;
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
    $form_vals = wpct_forms_ce_actions_map($form_vals);
    $form_vals = wpct_forms_ce_cleanup_empties($form_vals);
    return wpct_forms_ce_get_submission_payload($form_vals);
}

add_filter('wpct_forms_ce_prepare_submission', 'wpct_forms_ce_prepare_submission', 2, 10);
