<?php
add_action('gform_after_submission', 'wpct_forms_ce_api_submissions', 10, 2);
function wpct_forms_ce_api_submissions($entry, $form)
{
	$form_vals = wpct_gform_after_submission_build_call_body($entry, $form);
	if (isset($form_vals['source_xml_id'])) {
		$crmlead_apicall_body = wpct_forms_ce_get_crmlead_apicall_body($form_vals);
		$response = wpct_oc_post_odoo($crmlead_apicall_body, '/api/private/crm-lead');
		if ($response) {
			$response_json = json_decode($response['body']);
		} else {
			// Admin error email
			$to = wpct_oc_get_admin_notification_receiver();
			$subject = "Odoo subscription request submission error: Form(" . $form['id'] . ") Entry (" . $entry['id'] . ")";
			$body = "Submission subscription request for entry: " . $entry['id'] . " failed.<br/>Form id: " . $form['id'] . "<br/>Form title: " . $form['title'];
			wp_mail($to, $subject, $body);
		}
	} else {
		// throw new Exception("Error Processing Request", 1);
	}
}

function wpct_gform_after_submission_build_call_body($entry, $form)
{
	$form_vals = array(
		'entry_id' => $entry['id']
	);
	foreach ($form['fields'] as $field) {
		$inputs = $field->get_entry_inputs();
		// composed fields
		if (is_array($inputs)) {
			foreach ($inputs as $input) {
				$input_code = $field->inputName;
				if ($input['name']) {
					$form_vals[$input['name']] = rgar($entry, (string) $input['id']);
				}
			}
			// simple fields
		} else {
			$input_code = $field->inputName;
			if ($input_code) {
				$form_vals[$input_code] = rgar($entry, (string) $field->id);
			}
		}
	}

	return $form_vals;
}

function wpct_forms_ce_get_crmlead_apicall_body($form_vals)
{
	$body = array(
		'name' => $form_vals['source_xml_id'] . ' submission: ' . $form_vals['entry_id'],
		'metadata' => array()
	);
	foreach ($form_vals as $form_key => $form_val) {
		if ($form_key == 'company_id') {
			$body[$form_key] = (int)$form_val;
		} elseif ($form_key == 'email_from') {
			$body[$form_key] = $form_val;
		}
		$body['metadata'][] = array(
			'key' => $form_key,
			'value' => $form_val
		);
	}
	return $body;
}
