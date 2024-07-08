<?php

/*
Plugin Name:     Wpct ERP Forms
Plugin URI:      https://git.coopdevs.org/codeccoop/wp/wpct-erp-forms
Description:     Plugin to bridge WP forms submissions to a ERP backend
Author:          Còdec
Author URI:      https://www.codeccoop.org
Text Domain:     wpct-erp-forms
Domain Path:     /languages
Version:         2.0.1
*/

namespace WPCT_ERP_FORMS;

use WPCT_ERP_FORMS\WPCF7\Integration as Wpcf7Integration;
use WPCT_ERP_FORMS\GF\Integration as GFIntegration;
use WPCT_ABSTRACT\Plugin as BasePlugin;

if (!defined('ABSPATH')) {
    exit;
}

define('WPCT_ERP_FORMS_VERSION', '2.0.1');

require_once 'abstracts/class-singleton.php';
require_once 'abstracts/class-plugin.php';
require_once 'abstracts/class-menu.php';
require_once 'abstracts/class-settings.php';

require_once 'wpct-http-bridge/wpct-http-bridge.php';
require_once 'wpct-i18n/wpct-i18n.php';

require_once 'includes/class-integration.php';
require_once 'includes/class-menu.php';
require_once 'includes/class-settings.php';

class Wpct_Erp_Forms extends BasePlugin
{
    private $_integrations = [];

    public static $name = 'Wpct ERP Forms';
    public static $textdomain = 'wpct-erp-forms';

    protected static $menu_class = '\WPCT_ERP_FORMS\Menu';

    protected function __construct()
    {
        parent::__construct();

        if (apply_filters('wpct_is_plugin_active', false, 'contact-form-7/wp-contact-form-7.php')) {
            require_once 'includes/integrations/wpcf7/class-integration.php';
            $this->_integrations['wpcf7'] = Wpcf7Integration::get_instance();
        } elseif (apply_filters('wpct_is_plugin_active', false, 'gravityforms/gravityforms.php')) {
            require_once 'includes/integrations/gf/class-integration.php';
            $this->_integrations['gf'] = GFIntegration::get_instance();
        }

        add_filter('plugin_action_links', function ($links, $file) {
            if ($file !== plugin_basename(__FILE__)) {
                return $links;
            }

            $url = admin_url('options-general.php?page=wpct-erp-forms');
            $label = __('Settings');
            $link = "<a href='{$url}'>{$label}</a>";
            array_unshift($links, $link);
            return $links;
        }, 5, 2);
    }

    public function init()
    {
        add_filter('option_wpct-http-bridge_general', function () {
            return Settings::get_setting('wpct-erp-forms', 'general');
        });
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
}, 9);
