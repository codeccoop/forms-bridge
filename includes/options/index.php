<?php

use WPCT_ERP_FORMS\Options\Settings;
use WPCT_ERP_FORMS\Options\Menu;

require_once 'Settings.php';
require_once 'Menu.php';

function wpct_crm_forms_setup_menu()
{
    $menu = new Menu('Wpct ERP Forms', new Settings());

    add_action('admin_menu', function () use ($menu) {
        $menu->register();
    });

    add_action('admin_init', function () use ($menu) {
        $menu->getSettings()->register();
    });
}

wpct_crm_forms_setup_menu();
