<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\Menu as BaseMenu;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin menu class.
 */
class Menu extends BaseMenu
{
    /**
     * Renders the plugin menu page.
     */
    protected static function render_page($echo = true)
    {
        printf(
            '<div class="wrap" id="forms-bridge">%s</div>',
            esc_html__('Loading', 'forms-bridge')
        );
    }
}
