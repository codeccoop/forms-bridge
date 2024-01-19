<?php
add_action('wpct_erp_after_submission', 'wpct_erp_forms_do_submissions', 10, 2);
function wpct_erp_forms_do_submissions($entry, $form)
{
    $form_vals = wpct_erp_forms_parse_form_entry($entry, $form);

    // Exit submission if form isn't bound to Odoo
    if (!isset($form_vals['source_xml_id'])) return;

    $submission_payload = apply_filters('wpct_erp_forms_prepare_submission', $form_vals);

    $response = wpct_oc_post_odoo('/api/private/crm-lead', $submission_payload);
    if (!$response) {
        $to = wpct_erp_forms_option_getter('wpct_erp_forms_general', 'notification_receiver');
        $subject = 'Odoo subscription request submission error: Form(' . $form['id'] . ') Entry (' . $entry['id'] . ')';
        $body = 'Submission subscription request for entry: ' . $entry['id'] . ' failed.<br/>Form id: ' . $form['id'] . "<br/>Form title: " . $form['title'];
        wp_mail($to, $subject, $body);
    }
}
