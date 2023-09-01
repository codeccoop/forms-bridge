<?php

/**
 * Plugin Name:     Wpct Forms CE
 * Plugin URI:      https://git.coopdevs.org/coopdevs/website/wp/wp-plugins/wpct-forms-ce
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

/* Webhooks */
require_once "includes/webhooks.php";
require_once "includes/submissions.php";

/* Fields population */
require_once "includes/fields-population.php";

/* Dependencies */
add_filter('wpct_dependencies_check', function ($dependencies) {
    $dependencies['Gravity Forms'] = '<a href="https://www.gravityforms.com/">Gravity Forms</a>';
    $dependencies['Wpct Odoo Connect'] = '<a href="https://git.coopdevs.org/coopdevs/website/wp/wp-plugins/wpct-odoo-connect">Wpct Odoo Connect</a>';
    return $dependencies;
});
