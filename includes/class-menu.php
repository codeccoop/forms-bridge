<?php

namespace WPCT_ERP_FORMS;

use WPCT_ABSTRACT\Menu as BaseMenu;

class Menu extends BaseMenu
{
    protected static $settings_class = '\WPCT_ERP_FORMS\Settings';

    protected function render_page($echo = true)
    {
        printf(
            '<div class="wrap" id="wpct-erp-forms">%s</div>',
            esc_html__('Loading', 'wpct-erp-forms')
        );
    }
}
