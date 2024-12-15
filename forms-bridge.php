<?php

/*
Plugin Name:     Forms Bridge
Plugin URI:      https://git.coopdevs.org/codeccoop/wp/plugins/bridges/forms-bridge
Description:     Plugin to bridge WP forms submissions to any backend
Author:          Còdec
Author URI:      https://www.codeccoop.org
Text Domain:     forms-bridge
Domain Path:     /languages
Version:         1.2.2
*/

namespace FORMS_BRIDGE;

use Exception;
use WPCT_ABSTRACT\Plugin as BasePlugin;
use WPCT_ABSTRACT\Setting;

use function WPCT_ABSTRACT\is_list;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Handle plugin version.
 *
 * @var string FORMS_BRIDGE_VERSION Current plugin version.
 */
define('FORMS_BRIDGE_VERSION', '1.2.2');

require_once 'abstracts/class-plugin.php';

require_once 'deps/http/http-bridge.php';
require_once 'deps/i18n/wpct-i18n.php';

require_once 'includes/abstract-integration.php';
require_once 'addons/abstract-addon.php';

require_once 'includes/class-menu.php';
require_once 'includes/class-settings.php';
require_once 'includes/class-rest-settings-controller.php';
require_once 'includes/class-json-finger.php';
require_once 'includes/class-form-hook.php';

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

    protected static $settings_class = '\FORMS_BRIDGE\Settings';

    /**
     * Handle plugin menu class name.
     *
     * @var string $menu_class Plugin menu class name.
     */
    protected static $menu_class = '\FORMS_BRIDGE\Menu';

    /**
     * Initializes integrations and setup plugin hooks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        $this->load_integrations();
        $this->sync_http_setting();
        $this->wp_hooks();
        $this->custom_hooks();

        add_action(
            'forms_bridge_on_failure',
            function ($form_data, $submission, $error = '') {
                $this->notify_error($form_data, $submission, $error);
            },
            90,
            3
        );

        $addons = $this->addons();
        foreach ($addons as $addon => $enabled) {
            if ($enabled) {
                require_once plugin_dir_path(__FILE__) .
                    "addons/{$addon}/{$addon}.php";
            }
        }
    }

    /**
     * Loads plugin integrations.
     */
    private function load_integrations()
    {
        foreach (array_keys($this->_integrations) as $slug) {
            switch ($slug) {
                case 'wpcf7':
                    $plugin = 'contact-form-7/wp-contact-form-7.php';
                    break;
                case 'gf':
                    $plugin = 'gravityforms/gravityforms.php';
                    break;
                case 'wpforms':
                    $plugin = 'wpforms-lite/wpforms.php';
                    break;
            }

            $is_active = self::is_plugin_active($plugin);
            if ($is_active) {
                $NS = strtoupper($slug);
                require_once "includes/integrations/{$slug}/class-integration.php";
                $this->_integrations[$slug] = (
                    '\FORMS_BRIDGE\\' .
                    $NS .
                    '\Integration'
                )::get_instance();
            }
        }
    }

    /**
     * Synchronize plugin and http-bridge settings
     */
    private function sync_http_setting()
    {
        // Patch http bridge settings to plugin settings
        add_filter('option_forms-bridge_general', function ($value) {
            $backends = Settings::get_setting('http-bridge', 'general')
                ->backends;
            $value['backends'] = $backends;
            $value['addons'] = $this->addons();
            return $value;
        });

        // Syncronize plugin settings with http bridge settings
        function _forms_bridge_http_sync($option, $to)
        {
            if ($option !== 'forms-bridge_general') {
                return;
            }

            $http = Settings::get_setting('http-bridge', 'general');
            $http->backends = $to['backends'];
        }

        add_action(
            'add_option',
            static function ($option, $to) {
                _forms_bridge_http_sync($option, $to);
            },
            10,
            2
        );

        add_action(
            'updated_option',
            function ($option, $from, $to) {
                _forms_bridge_http_sync($option, $to);
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
            function ($form_hooks, $form_id) {
                if (!is_list($form_hooks)) {
                    $form_hooks = [];
                }

                return array_merge(
                    $form_hooks,
                    Form_Hook::form_hooks($form_id)
                );
            },
            5,
            2
        );

        // Return pair plugin registered forms datums
        add_filter(
            'forms_bridge_forms',
            function ($forms) {
                if (!is_array($forms)) {
                    $forms = [];
                }

                return array_merge($forms, $this->forms());
            },
            5
        );

        // Return current pair plugin form representation
        // If $form_id is passed, retrives form by ID.
        add_filter(
            'forms_bridge_form',
            function ($form_data, $form_id = null) {
                if (!is_array($form_data)) {
                    $form_data = [];
                }

                return array_merge($form_data, $this->form($form_id));
            },
            5
        );

        // Check if current form is bound to certain hook
        add_filter(
            'forms_bridge_submitting',
            function ($submitting, $hook_name) {
                $form = $this->form();
                return $submitting || isset($form['hooks'][$hook_name]);
            },
            5
        );

        // Return the current submission data
        add_filter(
            'forms_bridge_submission',
            function ($submission) {
                if (!is_array($submission)) {
                    $submission = [];
                }

                return array_merge($submission, $this->submission());
            },
            5
        );

        // Return the current submission uploaded files
        add_filter(
            'forms_bridge_uploads',
            function ($uploads) {
                if (!is_array($uploads)) {
                    $uploads = [];
                }

                return array_merge($uploads, $this->uploads());
            },
            5
        );

        add_filter(
            'forms_bridge_setting',
            function ($setting, $name) {
                if ($setting instanceof Setting) {
                    return $setting;
                }

                return Settings::get_setting('forms-bridge', $name);
            },
            5,
            2
        );
    }

    /**
     * Gets an array with the active integrations
     *
     * @return array Active integration instances.
     */
    private function integrations()
    {
        return array_values(
            array_filter(array_values($this->_integrations), function (
                $integration
            ) {
                return $integration;
            })
        );
    }

    private function addons()
    {
        $addons_dir = plugin_dir_path(__FILE__) . 'addons';
        $enableds = "{$addons_dir}/enabled";
        $addons = scandir($addons_dir);
        $registry = [];

        foreach ($addons as $addon) {
            if (in_array($addon, ['.', '..'])) {
                continue;
            }

            $addon_dir = "{$addons_dir}/{$addon}";
            $index = "{$addon_dir}/{$addon}.php";
            if (is_file($index)) {
                $registry[$addon] = is_file("{$enableds}/{$addon}");
            }
        }

        return $registry;
    }

    /**
     * Gets available forms' data.
     *
     * @return array Available forms' data.
     */
    private function forms()
    {
        $integrations = $this->integrations();

        $forms = [];
        foreach (array_values($integrations) as $integration) {
            $forms = array_merge($forms, $integration->forms());
        }

        return $forms;
    }

    /**
     * Gets form data, by context or by ID.
     *
     * @param int $form_id Form ID, optional.
     *
     * @return array|null Form data or null;
     */
    private function form($form_id = null)
    {
        $integrations = $this->integrations();

        foreach ($integrations as $integration) {
            if ($form_id) {
                $form = $integration->get_form_by_id($form_id);
            } else {
                $form = $integration->form();
            }

            if ($form) {
                return $form;
            }
        }
    }

    /**
     * Gets current submission data.
     *
     * @return array|null Submission data or null.
     */
    private function submission()
    {
        $integrations = $this->integrations();
        foreach ($integrations as $integration) {
            $submission = $integration->submission();
            if ($submission) {
                return $submission;
            }
        }
    }

    /**
     * Gets current submission uploads.
     *
     * @return array|null Uploaded files or null.
     */
    private function uploads()
    {
        $integrations = $this->integrations();
        foreach ($integrations as $integration) {
            $uploads = $integration->uploads();
            if ($uploads) {
                return $uploads;
            }
        }
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

        $dependencies = apply_filters('forms_bridge_admin_script_deps', [
            'react',
            'react-jsx-runtime',
            'wp-api-fetch',
            'wp-components',
            'wp-dom-ready',
            'wp-element',
            'wp-i18n',
            'wp-api',
        ]);

        wp_enqueue_script(
            $this->textdomain(),
            plugins_url('assets/wpfb.js', __FILE__),
            [],
            FORMS_BRIDGE_VERSION,
            ['in_footer' => false]
        );

        wp_enqueue_script(
            $this->textdomain() . '-admin',
            plugins_url('assets/plugin.bundle.js', __FILE__),
            $dependencies,
            FORMS_BRIDGE_VERSION,
            ['in_footer' => true]
        );

        wp_set_script_translations(
            $this->textdomain(),
            $this->textdomain(),
            plugin_dir_path(__FILE__) . 'languages'
        );

        wp_enqueue_style('wp-components');
    }

    /**
     * Sends error notifications to the email receiver.
     *
     * @param array $form_data Form data.
     * @param array $payload Submission data.
     * @param array $error_data Error data.
     */
    private function notify_error($form_data, $payload, $error = '')
    {
        $email = Settings::get_setting('forms-bridge', 'general')
            ->notification_receiver;

        if (empty($email)) {
            return;
        }

        $to = $email;
        $subject = 'Forms Bridge Error';
        $body = "Form ID: {$form_data['id']}\n";
        $body .= "Form title: {$form_data['title']}\n";
        $body .= 'Submission: ' . print_r($payload, true) . "\n";
        $body .= "Error: {$error}\n";
        $success = wp_mail($to, $subject, $body);
        if (!$success) {
            throw new Exception(
                'Error while submitting form ' . $form_data['id']
            );
        }
    }
}

// Start the plugin
Forms_Bridge::setup();
