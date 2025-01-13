<?php

/*
 * Plugin Name:         Forms Bridge
 * Plugin URI:          https://git.coopdevs.org/codeccoop/wp/plugins/bridges/forms-bridge
 * Description:         Plugin to bridge WP forms submissions to any backend
 * Author:              codeccoop
 * Author URI:          https://www.codeccoop.org
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         forms-bridge
 * Domain Path:         /languages
 * Version:             2.2
 * Requires PHP:        8.0
 * Requires at least:   6.7
 */

namespace FORMS_BRIDGE;

use Exception;
use WPCT_ABSTRACT\Plugin as BasePlugin;
use WPCT_ABSTRACT\Setting;

use function WPCT_ABSTRACT\is_list;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'abstracts/class-plugin.php';

require_once 'deps/http/http-bridge.php';
require_once 'deps/i18n/wpct-i18n.php';

require_once 'includes/class-logger.php';
require_once 'includes/class-menu.php';
require_once 'includes/class-settings.php';
require_once 'includes/class-rest-settings-controller.php';
require_once 'includes/class-json-finger.php';
require_once 'includes/class-form-hook.php';

require_once 'integrations/abstract-integration.php';
require_once 'addons/abstract-addon.php';

/**
 * Forms Bridge plugin.
 */
class Forms_Bridge extends BasePlugin
{
    /**
     * Handle plugin settings class name.
     *
     * @var string
     */
    protected static $settings_class = '\FORMS_BRIDGE\Settings';

    /**
     * Handle plugin menu class name.
     *
     * @var string
     */
    protected static $menu_class = '\FORMS_BRIDGE\Menu';

    public static function setting($name)
    {
        return apply_filters('forms_bridge_setting', null, $name);
    }

    /**
     * Initializes integrations and setup plugin hooks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        Addon::load();
        Integration::load();

        self::sync_http_setting();
        self::wp_hooks();
        self::custom_hooks();

        add_action(
            'forms_bridge_on_failure',
            static function (
                $payload,
                $attachments,
                $form_data,
                $error_data = []
            ) {
                self::notify_error(
                    $payload,
                    $attachments,
                    $form_data,
                    $error_data
                );
            },
            90,
            4
        );
    }

    /**
     * Synchronize plugin and http-bridge settings
     */
    private static function sync_http_setting()
    {
        $slug = self::slug();
        // Patch http bridge settings to plugin settings
        add_filter("option_{$slug}_general", static function ($value) {
            $backends = Settings::get_setting('http-bridge', 'general')
                ->backends;

            return array_merge($value, ['backends' => $backends]);
        });

        // Syncronize plugin settings with http bridge settings
        add_action(
            'updated_option',
            static function ($option, $from, $to) use ($slug) {
                if ($option !== $slug . '_general') {
                    return;
                }

                $http = Settings::get_setting('http-bridge', 'general');
                $http->backends = $to['backends'];
            },
            10,
            3
        );
    }

    /**
     * Binds plugin to wp hooks.
     */
    private static function wp_hooks()
    {
        // Enqueue plugin admin client scripts
        add_action('admin_enqueue_scripts', static function ($admin_page) {
            self::admin_enqueue_scripts($admin_page);
        });
    }

    /**
     * Adds plugin custom filters.
     */
    private static function custom_hooks()
    {
        // Return registerd form hooks
        add_filter(
            'forms_bridge_form_hooks',
            static function ($form_hooks, $integration, $form_id) {
                if (!is_list($form_hooks)) {
                    $form_hooks = [];
                }

                return array_merge(
                    $form_hooks,
                    Form_Hook::form_hooks($integration, $form_id)
                );
            },
            5,
            3
        );

        // Return pair plugin registered forms datums
        add_filter(
            'forms_bridge_forms',
            static function ($forms, $integration = null) {
                if (!is_array($forms)) {
                    $forms = [];
                }

                return array_merge($forms, self::forms($integration));
            },
            5,
            2
        );

        // Return current pair plugin form representation
        add_filter(
            'forms_bridge_form',
            static function ($form_data, $integration = null, $form_id = null) {
                if (!is_array($form_data)) {
                    $form_data = [];
                }

                return array_merge(
                    $form_data,
                    self::form($integration, $form_id)
                );
            },
            5,
            3
        );

        // Return the current submission data
        add_filter(
            'forms_bridge_submission',
            static function ($submission) {
                if (!is_array($submission)) {
                    $submission = [];
                }

                return array_merge($submission, self::submission());
            },
            5
        );

        // Return the current submission uploaded files
        add_filter(
            'forms_bridge_uploads',
            static function ($uploads) {
                if (!is_array($uploads)) {
                    $uploads = [];
                }

                return array_merge($uploads, self::uploads());
            },
            5
        );

        // Expose plugin settings with a filter by name.
        add_filter(
            'forms_bridge_setting',
            static function ($setting, $name) {
                if ($setting instanceof Setting) {
                    return $setting;
                }

                return Settings::get_setting(self::slug(), $name);
            },
            5,
            2
        );
    }

    /**
     * Gets available forms' data.
     *
     * @param string $integration Integration slug.
     *
     * @return array Available forms' data.
     */
    private static function forms($integration)
    {
        $integrations = Integration::integrations();

        if ($integration) {
            return $integrations[$integration]->forms();
        }

        $forms = [];
        foreach (array_values($integrations) as $integration) {
            $forms = array_merge($forms, $integration->forms());
        }

        return $forms;
    }

    /**
     * Gets form data, by context or by ID.
     *
     * @param string $integration Integration slug.
     * @param int $form_id Form ID, optional.
     *
     * @return array|null Form data or null;
     */
    private static function form($integration, $form_id)
    {
        $integrations = Integration::integrations();

        if ($integration) {
            $integrations = [$integration => $integrations[$integration]];
        } elseif ($form_id) {
            // Form id without integration is ambiguous, discard.
            return;
        }

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
    private static function submission()
    {
        $integrations = Integration::integrations();
        foreach (array_values($integrations) as $integration) {
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
    private static function uploads()
    {
        $integrations = Integration::integrations();
        foreach (array_values($integrations) as $integration) {
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
    private static function admin_enqueue_scripts($admin_page)
    {
        if ('settings_page_' . self::slug() !== $admin_page) {
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
            self::slug(),
            plugins_url('assets/wpfb.js', __FILE__),
            [],
            self::version(),
            ['in_footer' => false]
        );

        wp_enqueue_script(
            self::slug() . '-admin',
            plugins_url('assets/plugin.bundle.js', __FILE__),
            $dependencies,
            self::version(),
            ['in_footer' => true]
        );

        wp_set_script_translations(
            self::slug(),
            self::slug(),
            plugin_dir_path(__FILE__) . 'languages'
        );

        wp_enqueue_style('wp-components');
    }

    /**
     * Sends error notifications to the email receiver.
     *
     * @param array $payload Submission data.
     * @param array $attachments Submission attachments.
     * @param array $form_data Form data.
     * @param array $error_data Error data.
     */
    private static function notify_error(
        $payload,
        $attachments,
        $form_data,
        $error_data = []
    ) {
        $email = Settings::get_setting(self::slug(), 'general')
            ->notification_receiver;

        if (empty($email)) {
            return;
        }

        $error = print_r($error_data, true);
        $to = $email;
        $subject = 'Forms Bridge Error';
        $body = "Form ID: {$form_data['id']}\n";
        $body .= "Form title: {$form_data['title']}\n";
        $body .= 'Submission: ' . print_r($payload, true) . "\n";
        $body .= "Error: {$error}\n";

        $from_email = get_option('admin_email');
        $headers = ["From: Forms Bridge <{$from_email}>"];

        $success = wp_mail($to, $subject, $body, $headers, $attachments);
        if (!$success) {
            throw new Exception(
                'Error while submitting form ' . (int) $form_data['id']
            );
        }
    }
}

// Start the plugin
Forms_Bridge::setup();
