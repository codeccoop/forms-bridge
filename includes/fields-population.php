<?php

/* Placed on submissions.php to avoid overwritings of shortcodes params
/* add_filter('gform_field_value_odoo_company_id', 'wpct_forms_ce_populate_odoo_company_id'); */
/* function wpct_forms_ce_populate_odoo_company_id($value) */
/* { */
/*     if ($value) return $value; */
/*     return wpct_forms_ce_option_getter('wpct_forms_ce_general', 'coord_id'); */
/* } */

add_filter('gform_field_value_current_lang', 'wpct_forms_ce_populate_current_lang');
function wpct_forms_ce_populate_current_lang($value)
{
    if ($value) return $value;
    $current_lang = apply_filters("wpml_current_language", null);
    if (!$current_lang) $current_lang = 'ca';
    return $current_lang;
}
