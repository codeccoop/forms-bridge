<?php
add_action('gform_after_submission', 'wpct_crm_forms_api_submissions', 10, 2);
function wpct_crm_forms_api_submissions($entry, $form)
{
	$form_vals = wpct_crm_forms_parse_form_entry($entry, $form);

	if (!isset($form_vals['source_xml_id'])) {
		$form_id = rgat($form, 'id');
		$form_title = rgar($form, 'title');
		$form_vals['source_xml_id'] = "{$form_id}_{$form_title}";
	}

	$submission_payload = apply_filters('wpct_crm_forms_prepare_submission', $form_vals);

	$response = wpct_oc_post_odoo('/api/private/crm-lead', $submission_payload);
	if (!$response) {
		$to = wpct_crm_forms_option_getter('wpct_crm_forms_general', 'notification_receiver');
		$subject = 'Odoo subscription request submission error: Form(' . $form['id'] . ') Entry (' . $entry['id'] . ')';
		$body = 'Submission subscription request for entry: ' . $entry['id'] . ' failed.<br/>Form id: ' . $form['id'] . "<br/>Form title: " . $form['title'];
		wp_mail($to, $subject, $body);
	}
}
