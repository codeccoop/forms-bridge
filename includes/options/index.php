<?php

use WPCT_ERP_FORMS\Options\Settings;
use WPCT_ERP_FORMS\Options\Menu;

require_once 'Settings.php';
require_once 'Menu.php';

$wpct_erp_forms_admin_menu = new Menu('Wpct ERP Forms', new Settings());
$wpct_erp_forms_admin_menu->register();
