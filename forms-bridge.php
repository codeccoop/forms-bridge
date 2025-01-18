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
 * Version:             2.3.0
 * Requires PHP:        8.0
 * Requires at least:   6.7
 */

namespace FORMS_BRIDGE;

use Exception;
use WPCT_ABSTRACT\Plugin as BasePlugin;

use function WPCT_ABSTRACT\is_list;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'abstracts/class-plugin.php';

require_once 'deps/http/http-bridge.php';
require_once 'deps/i18n/wpct-i18n.php';

require_once 'includes/class-logger.php';
require_once 'includes/class-menu.php';
require_once 'includes/class-settings-store.php';
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
    protected static $settings_class = '\FORMS_BRIDGE\SettingsStore';

    /**
     * Handle plugin menu class name.
     *
     * @var string
     */
    protected static $menu_class = '\FORMS_BRIDGE\Menu';

    /**
     * Initializes integrations, addons and setup plugin hooks.
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
            $backends = \HTTP_BRIDGE\SettingsStore::setting('general')
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

                $http = \HTTP_BRIDGE\SettingsStore::setting('general');
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
            static function ($form_hooks, $form_id = null) {
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

        add_filter(
            'forms_bridge_form_hook',
            static function ($form_hook, $hook_name) {
                $form_hooks = apply_filters('forms_bridge_form_hooks', []);
                foreach ($form_hooks as $form_hook) {
                    if ($form_hook->name === $hook_name) {
                        return $form_hook;
                    }
                }
            },
            10,
            2
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
            static function ($default, $integration = null, $form_id = null) {
                return self::form($integration, $form_id);
            },
            5,
            3
        );

        // Return the current submission data
        add_filter(
            'forms_bridge_submission',
            static function () {
                return self::submission();
            },
            5
        );

        // Return the current submission uploaded files
        add_filter(
            'forms_bridge_uploads',
            static function () {
                return self::uploads();
            },
            5
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
            return isset($integrations[$integration])
                ? $integrations[$integration]->forms()
                : [];
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
            if (isset($integrations[$integration])) {
                $integrations = [$integration => $integrations[$integration]];
            } else {
                $integrations = [];
            }
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
        $slug = self::slug();
        $version = self::version();
        if ('settings_page_' . $slug !== $admin_page) {
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
            $slug,
            plugins_url('assets/wpfb.js', __FILE__),
            [],
            $version,
            ['in_footer' => false]
        );

        wp_enqueue_script(
            $slug . '-admin',
            plugins_url('assets/plugin.bundle.js', __FILE__),
            $dependencies,
            $version,
            ['in_footer' => true]
        );

        wp_set_script_translations(
            $slug . '-admin',
            $slug,
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
        $email = SettingsStore::setting('general')->notification_receiver;

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
