<?php
add_action('gform_after_submission', 'wpct_forms_ce_api_submissions', 10, 2);
function wpct_forms_ce_api_submissions($entry, $form)
{
	$form_vals = wpct_forms_ce_parse_form_entry($entry, $form);
	if (!isset($form_vals['source_xml_id'])) throw new Exception('Error Processing Request', 400);
	$submission_payload = apply_filters('wpct_forms_ce_prepare_submission', $form_vals);

	$response = wpct_oc_post_odoo('/api/private/crm-lead', $submission_payload);
	if (!$response) {
		$to = wpct_forms_ce_option_getter('wpct_forms_ce_general', 'notification_receiver');
		$subject = 'Odoo subscription request submission error: Form(' . $form['id'] . ') Entry (' . $entry['id'] . ')';
		$body = 'Submission subscription request for entry: ' . $entry['id'] . ' failed.<br/>Form id: ' . $form['id'] . "<br/>Form title: " . $form['title'];
		wp_mail($to, $subject, $body);
	}
}
