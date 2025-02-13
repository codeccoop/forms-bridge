<?php

/*
 * Plugin Name:         Forms Bridge
 * Plugin URI:          https://git.coopdevs.org/codeccoop/wp/plugins/bridges/forms-bridge
 * Description:         Plugin to bridge WP forms submissions to any backend or service
 * Author:              codeccoop
 * Author URI:          https://www.codeccoop.org
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         forms-bridge
 * Domain Path:         /languages
 * Version:             2.3.5
 * Requires PHP:        8.0
 * Requires at least:   6.7
 */

namespace FORMS_BRIDGE;

use Exception;
use WPCT_ABSTRACT\Plugin as Base_Plugin;

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
require_once 'includes/class-form-bridge.php';
require_once 'includes/class-form-bridge-template.php';

require_once 'integrations/abstract-integration.php';
require_once 'addons/abstract-addon.php';

/**
 * Forms Bridge plugin.
 */
class Forms_Bridge extends Base_Plugin
{
    /**
     * Handle plugin settings class name.
     *
     * @var string
     */
    protected static $settings_class = '\FORMS_BRIDGE\Settings_Store';

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

        self::wp_hooks();
        self::custom_hooks();

        add_action(
            'forms_bridge_on_failure',
            static function ($bridge, $error, $payload, $attachments) {
                self::notify_error($bridge, $error, $payload, $attachments);
            },
            90,
            4
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
            static function ($default, $form_id = null, $integration = null) {
                return self::form($form_id, $integration);
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
     * @param int $form_id Form ID, optional.
     * @param string $integration Integration slug.
     *
     * @return array|null Form data or null;
     */
    private static function form($form_id, $integration)
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
     * @param Form_Bridge $bridge Bridge instance.
     * @param WP_Error $error Error instance.
     * @param array $payload Submission data.
     * @param array $attachments Submission attachments.
     */
    private static function notify_error(
        $bridge,
        $error,
        $payload,
        $attachments
    ) {
        $email = Settings_Store::setting('general')->notification_receiver;

        if (empty($email)) {
            return;
        }

        $form_data = $bridge->form;
        $error = print_r($error->get_error_data(), true);
        $to = $email;
        $subject = 'Forms Bridge Error';
        $body = "Form ID: {$form_data['id']}\n";
        $body .= "Form title: {$form_data['title']}\n";
        $body .= "Bridge: {$bridge->name}\n";
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
