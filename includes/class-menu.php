<?php

namespace FORMS_BRIDGE;

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
    protected static $settings_class = '\FORMS_BRIDGE\Settings';

    /**
     * Render plugin menu page.
     *
     * @since 3.0.0
     */
    protected function render_page($echo = true)
    {
        printf(
            '<div class="wrap" id="forms-bridge">%s</div>',
            esc_html__('Loading', 'forms-bridge')
        );
    }
}
