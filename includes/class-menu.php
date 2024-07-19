<?php

namespace WPCT_ERP_FORMS;

use WPCT_ABSTRACT\Menu as BaseMenu;

class Menu extends BaseMenu
{
	static protected $settings_class = '\WPCT_ERP_FORMS\Settings';

    protected function render_page($echo = true)
    {
        $output = parent::render_page(false);
        echo apply_filters('wpct_erp_forms_menu_page_content', $output);
    }
}
