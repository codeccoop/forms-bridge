<?php

if (!defined('ABSPATH')) {
    exit;
}

define('WPCT_ERP_FORMS_IBAN_FIELD_VERSION', '1.0');
add_action('gform_loaded', 'wpct_erp_forms_load_iban_field', 5);
function wpct_erp_forms_load_iban_field()
{
    if (!method_exists('GFForms', 'include_addon_framework')) return;
    require_once 'Addon.php';
    require_once 'Field.php';


    GFAddOn::register(\wpct_erp_FORMS\IBAN_Field\Addon::class);
}
