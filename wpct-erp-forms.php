<?php

/**
 * Plugin Name:     Wpct ERP Forms
 * Plugin URI:      https://git.coopdevs.org/codeccoop/wp/wpct-erp-forms
 * Description:     Plugin to bridge WP forms submissions to a ERP backend
 * Author:          Còdec Cooperativa
 * Author URI:      https://www.codeccoop.org
 * Text Domain:     wpct-erp-forms
 * Domain Path:     languages
 * Version:         1.0.0
 *
 * @package         wpct_erp_forms
 */

/* Settings */
require_once 'includes/options/index.php';

/* Integrations */
require_once 'includes/integrations/index.php';

/* Fields */
require_once 'includes/fields/index.php';

/* Dependencies */
add_filter('wpct_dependencies_check', function ($dependencies) {
    $dependencies['Wpct Odoo Connect'] = '<a href="https://git.coopdevs.org/coopdevs/website/wp/wp-plugins/wpct-odoo-connect">Wpct Odoo Connect</a>';
    return $dependencies;
});

/* Localization */
add_action('plugins_loaded', 'wpct_erp_forms_i18n', 10);
function wpct_erp_forms_i18n()
{
    load_plugin_textdomain(
        'wpct-erp-forms',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
