<?php

/**
 * Gent gform entry and form objects and parse it to a form values array
 */
function wpct_crm_forms_parse_form_entry($entry, $form)
{
    $form_vals = array(
        'entry_id' => $entry['id']
    );

    foreach ($form['fields'] as $field) {
        if ($field->type === 'consent') continue;

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
                        $values[] = wpct_crm_forms_format_value($value, $field, $input);
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
    if ($field->type === 'fileupload') {
        if (!is_array($field->get_entry_inputs())) return json_decode($value)[0];
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
    $payload = array(
        'name' => $form_vals['source_xml_id'] . ' submission: ' . $form_vals['entry_id'],
        'metadata' => array()
    );

    foreach ($form_vals as $key => $val) {
        if ($key == 'company_id') {
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
add_filter('wpct_crm_forms_prepare_submission', 'wpct_crm_forms_prepare_submission', 10, 2);
function wpct_crm_forms_prepare_submission($form_vals)
{
    $form_vals = wpct_crm_forms_add_cord_id($form_vals);
    $form_vals = wpct_crm_forms_cleanup_empties($form_vals);
    return wpct_crm_forms_get_submission_payload($form_vals);
}


/**
 * Store uploads on a custom folder
 */
add_filter('gform_upload_path', 'wpct_crm_forms_upload_path', 90, 2);
function wpct_crm_forms_upload_path($path_info, $form_id)
{
    $upload_dir = wp_upload_dir();
    $base_path = apply_filters('wpct_crm_forms_upload_path', $upload_dir['basedir'] . '/crm-forms');
    if (!($base_path && is_string($base_path))) throw new Exception('WPCT CRM Forms: Invalid upload path');
    $base_path = preg_replace('/\/$/', '', $base_path);

    $path = $base_path . '/' . implode('/', [$form_id, date('Y'), date('m')]);
    if (!is_dir($path)) mkdir($path, 0700, true);
    $path_info['path'] = $path;

    $url = get_site_url() . '/index.php?';
    $url .= 'crm-forms-attachment=' . urlencode(str_replace($base_path, '', $path) . '/');
    $path_info['url'] = $url;

    return $path_info;
};

add_action('init', 'wpct_crm_forms_download_file');
function wpct_crm_forms_download_file()
{
    if (!isset($_GET['crm-forms-attachment'])) return;

    $upload_dir = wp_upload_dir();
    $base_path = apply_filters('wpct_crm_forms_upload_path', $upload_dir['basedir'] . '/crm-forms');
    if (!($base_path && is_string($base_path))) throw new Exception('WPCT CRM Forms: Invalid upload path');
    $base_path = preg_replace('/\/$/', '', $base_path);
    $path = $base_path . urldecode($_GET['crm-forms-attachment']);

    if (!(is_user_logged_in() && file_exists($path))) {
        global $wp_query;
        status_header(404);
        $wp_query->set_404();
        $template_path = get_404_template();
        if (file_exists($template_path)) require_once($template_path);
        die();
    }

    $filetype = wp_check_filetype($path);
    if (!$filetype['type']) {
        $filetype['type'] = mime_content_type($path);
    }

    nocache_headers();
    header('X-Robots-Tag: noindex', true);
    header('Content-Type: ' . $filetype['type']);
    header('Content-Description: File Transfer');
    header('Content-Disposition: inline; filename="' . wp_basename($path) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($path));

    if (ob_get_contents()) ob_end_clean();

    readfile($path);
    die();
}
