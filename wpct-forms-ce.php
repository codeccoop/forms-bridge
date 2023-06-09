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

/* Webhooks */
require_once "includes/webhooks.php";
require_once "includes/submissions.php";

/* Fields population */
require_once "includes/fields-population.php";

add_action('admin_init', 'wpct_forms_ce_init', 10);
function wpct_forms_ce_init()
{
    add_filter('wpct_dependencies_check', function ($dependencies) {
        $dependencies['gravityforms/gravityforms.php'] = '<a href="https://www.gravityforms.com/">Gravity Forms</a>';
        return $dependencies;
    });
}
