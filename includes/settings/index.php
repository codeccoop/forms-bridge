<?php

use WPCT_ERP_FORMS\Settings\Setting;
use WPCT_ERP_FORMS\Settings\Menu;

require_once 'Setting.php';
require_once 'Menu.php';

$wpct_erp_forms_setting = new Setting();
$wpct_erp_forms_menu = new Menu($wpct_erp_forms_setting);

add_action('admin_menu', function () use ($wpct_erp_forms_menu) {
    $wpct_erp_forms_menu->register();
});

add_action('admin_init', function () use ($wpct_erp_forms_setting) {
    $wpct_erp_forms_setting->register();
});
