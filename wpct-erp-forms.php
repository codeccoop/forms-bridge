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
	private $_refs = null;

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

        add_filter('option_wpct-http-bridge_general', function () {
            return Settings::get_setting('wpct-erp-forms', 'general');
        });

        add_filter('wpct_erp_forms_form_ref', function ($null, $form_id) {
            return $this->get_form_ref($form_id);
        }, 10, 2);

        add_filter('option_wpct-erp-forms_rest-api', function ($setting) {
            return $this->populate_refs($setting);
        }, 10, 1);

        add_filter('option_wpct-erp-forms_rpc-api', function ($setting) {
            return $this->populate_refs($setting);
        }, 10, 1);

        add_action('update_option', function ($option, $from, $to) {
            $plugin = Wpct_Erp_Forms::get_instance();
            if ($option === 'wpct-erp-forms_rest-api' || $option === 'wpct-erp-forms_rpc-api') {
                foreach ($to['forms'] as $form) {
                    $plugin->set_form_ref($form['form_id'], $form['ref']);
                }
            }
        }, 10, 3);
    }

    public function init()
    {
    }

    public static function activate()
    {
    }

    public static function deactivate()
    {
    }

	private function get_form_refs()
	{
		if (empty($this->_refs)) {
			$this->_refs = get_option('wpct-erp-forms_refs', []);
		}

		return $this->_refs;
	}

	private function set_form_refs($refs)
	{
		$this->_refs = $refs;
        update_option('wpct-erp-forms_refs', $refs);
	}

    public function get_form_ref($form_id)
    {
		$refs = $this->get_form_refs();
        foreach ($refs as $ref_id => $ref) {
            if ((string) $ref_id === (string) $form_id) {
                return $ref;
            }
        }

        return null;
    }

    public function set_form_ref($form_id, $ref)
    {
		$refs = $this->get_form_refs();
        $refs[$form_id] = $ref;
		$this->set_form_refs($refs);
    }

    private function populate_refs($setting)
    {
		$refs = $this->get_form_refs();
        for ($i = 0; $i < count($setting['forms']); $i++) {
            $form = $setting['forms'][$i];
            if (!isset($refs[$form['form_id']])) {
                continue;
            }
            $form['ref'] = $refs[$form['form_id']];
            $setting['forms'][$i] = $form;
        }

        return $setting;
    }
}

add_action('plugins_loaded', function () {
    $plugin = Wpct_Erp_Forms::get_instance();
}, 9);
