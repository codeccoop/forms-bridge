<?php

namespace WPCT_ERP_FORMS;

use WPCT_ABSTRACT\Menu as BaseMenu;

/**
 * Plugin menu class.
 *
 * @since 1.0.0
 */
class Menu extends BaseMenu
{
    /**
     * Handle plugin settings class name.
     *
     * @since 3.0.0
     *
     * @var string $settings_class Settings class name.
     */
    protected static $settings_class = '\WPCT_ERP_FORMS\Settings';

    /**
     * Render plugin menu page.
     *
     * @since 3.0.0
     */
    protected function render_page($echo = true)
    {
        printf(
            '<div class="wrap" id="wpct-erp-forms">%s</div>',
            esc_html__('Loading', 'wpct-erp-forms')
        );
    }
}
