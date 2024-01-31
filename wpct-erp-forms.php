<?php

namespace WPCT_ERP_FORMS;

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

if (!defined('ABSPATH')) {
    exit;
}

require_once 'includes/class-singleton.php';
require_once 'includes/class-plugin.php';
require_once 'includes/class-menu.php';
require_once 'includes/class-settings.php';
require_once 'includes/class-integration.php';
require_once 'includes/class-field.php';

class Wpct_Erp_Forms extends Plugin
{

    private $_integrations = [];

    protected $name = 'Wpct ERP Forms';
    protected $textdomain = 'wpct-erp-forms';
    protected $dependencies = [
        'Wpct Http Backend' => '<a href="https://git.coopdevs.org/codeccoop/wp/wpct-http-backend/">Wpct Http Backend</a>'
    ];

    protected function __construct()
    {
        parent::__construct();

        if (apply_filters('wpct_dc_is_plugin_active', false, 'contact-form-7/wp-contact-form-7.php')) {
            require_once 'includes/integrations/wpcf7/class-integration.php';
            $this->_integrations['wpcf7'] = Wpcf7Integration::get_instance();
        } else if (apply_filters('wpct_dc_is_plugin_active', false, 'gravityforms/gravityforms.php')) {
            require_once 'includes/integrations/gf/class-integration.php';
            $this->_integrations['gf'] = GFIntegration::get_instance();
        }
    }

    public function init()
    {
        foreach ($this->_integrations as $integration) {
            $integration->init();
        }
    }

    public static function activate()
    {
    }

    public static function deactivate()
    {
    }
}

add_action('plugins_loaded', function () {
    $plugin = Wpct_Erp_Forms::get_instance();
}, 10);
