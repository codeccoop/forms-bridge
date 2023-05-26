<?php

/**
 * Plugin Name:     Wpct Forms CE
 * Plugin URI:      https://git.coopdevs.org/coopdevs/website/wp/wp-plugins
 * Description:     Configuration options for CE forms
 * Author:          Coopdevs Treball SCCL
 * Author URI:      https://coopdevs.org
 * Text Domain:     wpct-forms-ce
 * Domain Path:     /languages
 * Version:         0.1.3
 *
 * @package         Wpct_Forms_CE
 */

/* Options Page */
require_once "includes/options-page.php";

/* Lang population */
add_filter('gform_field_value_current_lang', 'wpct_forms_ce_populate_current_lang');
function wpct_forms_ce_populate_current_lang($value)
{
    return apply_filters('wpml_current_language', NULL);
}

/* Error Handling */
add_action('gform_webhooks_post_request', 'wpct_forms_ce_control_error', 10, 4);
function wpct_forms_ce_control_error($response, $feed, $entry, $form)
{
    if ($response['response']['code'] != 200) {
        $ocSettings = get_option("wpct_forms_ce_settings");
        if (isset($ocSettings['wpct_odoo_connect_notification_receiver'])) {
            $to = $ocSettings['wpct_odoo_connect_notification_receiver'];
            $subject = "somcomunitats Webhook " . $form['id'] . "_" . $entry['id'] . " failed!";
            $body = "Webhook for entry: " . $entry['id'] . " failed.<br/>Form id: " . $form['id'] . "<br/>Form title: " . $form['title'];
            wp_mail($to, $subject, $body);
        }
    }
}

add_action('admin_init', 'wpct_forms_ce_init', 10);
function wpct_forms_ce_init()
{
    add_filter('wpct_dependencies_check', function ($dependencies) {
        $dependencies['gravityforms/gravityforms.php'] = '<a href="https://www.gravityforms.com/">Gravity Forms</a>';
        return $dependencies;
    });
}
