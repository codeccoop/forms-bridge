<?php

/*
Plugin Name:     Forms Bridge
Plugin URI:      https://git.coopdevs.org/codeccoop/wp/plugins/bridges/forms-bridge
Description:     Plugin to bridge WP forms submissions to any backend
Author:          Còdec
Author URI:      https://www.codeccoop.org
Text Domain:     forms-bridge
Domain Path:     /languages
Version:         1.1.0
*/

namespace FORMS_BRIDGE;

use FORMS_BRIDGE\WPCF7\Integration as Wpcf7Integration;
use FORMS_BRIDGE\GF\Integration as GFIntegration;
use FORMS_BRIDGE\WPFORMS\Integration as WPFormsIntegration;
use WPCT_ABSTRACT\Plugin as BasePlugin;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Handle plugin version.
 *
 * @var string FORMS_BRIDGE_VERSION Current plugin version.
 */
define('FORMS_BRIDGE_VERSION', '1.1.0');

require_once 'abstracts/class-plugin.php';

require_once 'deps/http/http-bridge.php';
require_once 'deps/i18n/wpct-i18n.php';

require_once 'includes/abstract-integration.php';
require_once 'includes/class-menu.php';
require_once 'includes/class-settings.php';
require_once 'includes/class-rest-settings-controller.php';
require_once 'includes/class-json-finger.php';

/**
 * Forms Bridge plugin.
 */
class Forms_Bridge extends BasePlugin
{
    /**
     * Handle plugin active integrations.
     *
     * @var array $_integrations
     */
    private $_integrations = [
        'gf' => null,
        'wpforms' => null,
        'wpcf7' => null,
    ];

    /**
     * Handle plugin name.
     *
     * @var string $name Plugin name.
     */
    public static $name = 'Forms Bridge';

    /**
     * Handle plugin textdomain.
     *
     * @var string $textdomain Plugin text domain.
     */
    public static $textdomain = 'forms-bridge';

    /**
     * Handle plugin menu class name.
     *
     * @var string $menu_class Plugin menu class name.
     */
    protected static $menu_class = '\FORMS_BRIDGE\Menu';

    /**
     * Starts the plugin.
     */
    public static function start()
    {
        return self::get_instance();
    }

    /**
     * Initializes the plugin on wp init.
     */
    public function init()
    {
    }

    /**
     * Callback to activation hook.
     */
    public static function activate()
    {
    }

    /**
     * Callback to deactivation hook.
     */
    public static function deactivate()
    {
    }

    /**
     * Initializes integrations and setup plugin hooks.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->load_integrations();
        $this->sync_http_setting();
        $this->wp_hooks();
        $this->custom_hooks();
    }

    /**
     * Loads plugin integrations.
     */
    private function load_integrations()
    {
        if (
            apply_filters(
                'wpct_is_plugin_active',
                false,
                'contact-form-7/wp-contact-form-7.php'
            )
        ) {
            require_once 'includes/integrations/wpcf7/class-integration.php';
            $this->_integrations['wpcf7'] = Wpcf7Integration::get_instance();
        } elseif (
            apply_filters(
                'wpct_is_plugin_active',
                false,
                'gravityforms/gravityforms.php'
            )
        ) {
            require_once 'includes/integrations/gf/class-integration.php';
            $this->_integrations['gf'] = GFIntegration::get_instance();
        } elseif (
            apply_filters(
                'wpct_is_plugin_active',
                false,
                'wpforms-lite/wpforms.php'
            )
        ) {
            require_once 'includes/integrations/wpforms/class-integration.php';
            $this->_integrations[
                'wpforms'
            ] = WPFormsIntegration::get_instance();
        }
    }

    /**
     * Synchronize plugin and http-bridge settings
     */
    private function sync_http_setting()
    {
        // Patch http bridge settings to plugin settings
        add_filter('option_forms-bridge_general', function ($value) {
            $backends = Settings::get_setting(
                'http-bridge',
                'general',
                'backends'
            );
            $value['backends'] = $backends;
            return $value;
        });

        // Syncronize plugin settings with http bridge settings
        add_action(
            'updated_option',
            function ($option, $from, $to) {
                if ($option !== 'forms-bridge_general') {
                    return;
                }

                $http_setting = Settings::get_setting('http-bridge', 'general');
                $http_setting['backends'] = $to['backends'];
                update_option('http-bridge_general', $http_setting);
            },
            10,
            3
        );
    }

    /**
     * Binds plugin to wp hooks.
     */
    private function wp_hooks()
    {
        // Add link to submenu page on plugins page
        add_filter(
            'plugin_action_links',
            function ($links, $file) {
                if ($file !== plugin_basename(__FILE__)) {
                    return $links;
                }

                $url = admin_url('options-general.php?page=forms-bridge');
                $label = __('Settings');
                $link = "<a href='{$url}'>{$label}</a>";
                array_unshift($links, $link);
                return $links;
            },
            5,
            2
        );

        // Enqueue plugin admin client scripts
        add_action('admin_enqueue_scripts', function ($admin_page) {
            $this->admin_enqueue_scripts($admin_page);
        });
    }

    /**
     * Adds plugin custom filters.
     */
    private function custom_hooks()
    {
        // Return registerd form hooks
        add_filter(
            'forms_bridge_form_hooks',
            function ($default, $form_id) {
                return $this->get_form_hooks($form_id);
            },
            10,
            2
        );

        // Return pair plugin registered forms datums
        add_filter('forms_bridge_forms', function () {
            $integration = $this->get_integration();
            if (!$integration) {
                return [];
            }

            return $integration->get_forms();
        });

        // Return current pair plugin form representation
        // If $form_id is passed, retrives form by ID.
        add_filter('forms_bridge_form', function ($default, $form_id = null) {
            $integration = $this->get_integration();
            if (!$integration) {
                return null;
            }

            if ($form_id) {
                return $integration->get_form_by_id($form_id);
            } else {
                return $integration->get_form();
            }
        });

        // Check if current form is bound to certain hook
        add_filter('forms_bridge_is_hooked', function ($default, $hook_name) {
            $integration = $this->get_integration();
            if (!$integration) {
                return false;
            }

            $form = $integration->get_form();
            if (!$form) {
                return false;
            }

            return isset($form['hooks'][$hook_name]);
        });

        // Return the current submission data
        add_filter('forms_bridge_submission', function () {
            $integration = $this->get_integration();
            if (!$integration) {
                return null;
            }

            return $integration->get_submission();
        });

        // Return the current submission uploaded files
        add_filter('forms_bridge_uploads', function () {
            $integration = $this->get_integration();
            if (!$integration) {
                return null;
            }

            return $integration->get_uploads();
        });

        add_filter(
            'forms_bridge_backend',
            function ($default, $name) {
                return apply_filters('http_bridge_backend', $default, $name);
            },
            10,
            2
        );

        add_filter('forms_bridge_backends', function () {
            return apply_filters('http_bridge_backends');
        });
    }

    /**
     * Current integration getter.
     *
     * @return object $integration
     */
    private function get_integration()
    {
        foreach (array_values($this->_integrations) as $integration) {
            if ($integration) {
                return $integration;
            }
        }
    }

    /**
     * Form hooks getter.
     *
     * @return array $hooks Array with hooks.
     */
    private function get_form_hooks($form_id)
    {
        if (empty($form_id)) {
            $integration = $this->get_integration();
            if (!$integration) {
                return [];
            }

            $form = $integration->get_form();
            if (!$form) {
                return [];
            }

            $form_id = $form['id'];
        }

        $rest_hooks = Settings::get_setting(
            'forms-bridge',
            'rest-api',
            'form_hooks'
        );
        $rpc_hooks = Settings::get_setting(
            'forms-bridge',
            'rpc-api',
            'form_hooks'
        );

        return array_reduce(
            array_merge($rest_hooks, $rpc_hooks),
            function ($hooks, $hook) use ($form_id) {
                if ((int) $hook['form_id'] === (int) $form_id) {
                    $hooks[$hook['name']] = $hook;
                }

                return $hooks;
            },
            []
        );
    }

    /**
     * Enqueue admin client scripts
     *
     * @param string $admin_page Current admin page.
     */
    private function admin_enqueue_scripts($admin_page)
    {
        if ('settings_page_forms-bridge' !== $admin_page) {
            return;
        }

        wp_enqueue_script(
            $this->get_textdomain(),
            plugins_url('assets/plugin.bundle.js', __FILE__),
            [
                'react',
                'react-jsx-runtime',
                'wp-api-fetch',
                'wp-components',
                'wp-dom-ready',
                'wp-element',
                'wp-i18n',
                'wp-api',
            ],
            FORMS_BRIDGE_VERSION,
            ['in_footer' => true]
        );

        wp_set_script_translations(
            $this->get_textdomain(),
            $this->get_textdomain(),
            plugin_dir_path(__FILE__) . 'languages'
        );

        wp_enqueue_style('wp-components');
    }
}

// Setup plugin on wp plugins_loaded hook
add_action('plugins_loaded', ['\FORMS_BRIDGE\Forms_Bridge', 'start'], 9);
