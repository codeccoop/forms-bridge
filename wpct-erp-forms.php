<?php

namespace WPCT_ERP_FORMS;

use Exception;
use WPCT_ERP_FORMS\WPCF7\Integration as Wpcf7Integration;
use WPCT_ERP_FORMS\GF\Integration as GFIntegration;

/**
 * Plugin Name:     Wpct ERP Forms
 * Plugin URI:      https://git.coopdevs.org/codeccoop/wp/wpct-erp-forms
 * Description:     Plugin to bridge WP forms submissions to a ERP backend
 * Author:          Còdec Cooperativa
 * Author URI:      https://www.codeccoop.org
 * Text Domain:     wpct-erp-forms
 * Domain Path:     languages
 * Version:         1.0.0
 *
 * @package         wpct_erp_forms
 */

require_once 'includes/class-menu.php';
require_once 'includes/class-settings.php';
require_once 'includes/class-integration.php';
require_once 'includes/fields/class-field.php';

class Plugin
{
    private $menu;
    private $integrations = [];

    public function __construct()
    {
        $settings = new Settings();
        $this->menu = new Menu('Wpct ERP Forms', $settings);

        load_plugin_textdomain(
            'wpct-erp-forms',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages',
        );
    }

    public function on_load()
    {
        add_action('init', function () {
            /* Dependencies */
            add_filter('wpct_dependencies_check', function ($dependencies) {
                $dependencies['Wpct Http Backend'] = '<a href="https://git.coopdevs.org/codeccoop/wp/wpct-http-backend/">Wpct Http Backend</a>';
                return $dependencies;
            });

            if (apply_filters(false, 'wp-contact-form-7/wp-contact-form-7.php')) {
                require_once 'includes/integrations/wpcf7/class-integration.php';
                $this->integrations['wpcf7'] = new Wpcf7Integration();
            } else if (apply_filters(false, 'gravityforms/gravityforms.php')) {
                require_once 'includes/integrations/gf/class-integration.php';
                $this->integrations['gf'] = new GFIntegration();
            }
        });

        $this->menu->on_load();
    }
}

add_action('plugins_loaded', function () {
    $plugin = new Plugin();
    $plugin->on_load();
}, 10);
