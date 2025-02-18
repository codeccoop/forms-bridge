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

use Error;
use Exception;
use WP_Error;
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

        self::http_hooks();
        self::wp_hooks();

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
     * Aliases to the http bride filters API.
     */
    private static function http_hooks()
    {
        add_filter(
            'forms_bridge_backends',
            static function ($backends) {
                return apply_filters('http_bridge_backends', $backends);
            },
            10,
            1
        );

        add_filter(
            'forms_bridge_backend',
            static function ($backend, $name) {
                return apply_filters('http_bridge_backend', $backend, $name);
            },
            10,
            2
        );

        add_filter(
            'http_bridge_backend_headers',
            static function ($headers, $backend) {
                return apply_filters(
                    'forms_bridge_backend_headers',
                    $headers,
                    $backend
                );
            },
            99,
            2
        );

        add_filter(
            'http_bridge_backend_url',
            static function ($url, $backend) {
                return apply_filters(
                    'forms_bridge_backend_url',
                    $url,
                    $backend
                );
            },
            99,
            2
        );

        add_filter(
            'http_bridge_response',
            static function ($response, $request) {
                return apply_filters(
                    'forms_bridge_http_response',
                    $response,
                    $request
                );
            },
            99,
            2
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

        add_action(
            'upgrader_process_complete',
            static function ($upgrader, $extra) {
                if (
                    $extra['type'] === 'plugin' &&
                    in_array(self::index(), $extra['plugins'])
                ) {
                    self::do_migrations();
                }
            },
            10,
            2
        );
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
     * Proceed with the submission sub-routine.
     */
    public static function do_submission()
    {
        $form_data = apply_filters('forms_bridge_form', null);
        if (!$form_data) {
            return;
        }

        if (empty($form_data['bridges'])) {
            return;
        }

        Logger::log('Form data');
        Logger::log($form_data);

        $bridges = $form_data['bridges'];

        $submission = apply_filters('forms_bridge_submission', null);
        Logger::log('Form submission');
        Logger::log($submission);

        if (empty($submission)) {
            return;
        }

        $uploads = apply_filters('forms_bridge_uploads', []);
        Logger::log('Submission uploads');
        Logger::log($uploads);

        foreach (array_values($bridges) as $bridge) {
            try {
                // TODO: Exclude attachments from payload finger mangling
                $payload = $bridge->apply_pipes($submission);
                Logger::log('Submission payload after pipes');
                Logger::log($payload);

                $prune_empties = apply_filters(
                    'forms_bridge_prune_empties',
                    false,
                    $bridge
                );

                if ($prune_empties) {
                    $payload = self::prune_empties($payload);
                    Logger::log('Submission payload after prune empties');
                    Logger::log($payload);
                }

                $attachments = apply_filters(
                    'forms_bridge_attachments',
                    self::attachments($uploads),
                    $bridge
                );

                if (!empty($attachments)) {
                    $content_type = $bridge->content_type;
                    if (
                        in_array($content_type, [
                            'application/json',
                            'application/x-www-form-urlencoded',
                        ])
                    ) {
                        $attachments = self::stringify_attachments(
                            $attachments
                        );
                        foreach ($attachments as $name => $value) {
                            $payload[$name] = $value;
                        }
                        $attachments = [];
                        Logger::log(
                            'Submission payload after attachments stringify'
                        );
                        Logger::log($payload);
                    }
                }

                $payload = apply_filters(
                    'forms_bridge_payload',
                    $payload,
                    $bridge
                );

                Logger::log('Filtered submission payload');
                Logger::log($payload);

                if (empty($payload)) {
                    continue;
                }

                $skip = apply_filters(
                    'forms_bridge_skip_submission',
                    false,
                    $bridge,
                    $payload,
                    $attachments
                );

                if ($skip) {
                    Logger::log('Skip submission');
                    continue;
                }

                do_action(
                    'forms_bridge_before_submission',
                    $bridge,
                    $payload,
                    $attachments
                );

                $response = $bridge->submit($payload, $attachments);
                Logger::log('Submission response');
                Logger::log($response['response']);

                if ($error = is_wp_error($response) ? $response : null) {
                    do_action(
                        'forms_bridge_on_failure',
                        $bridge,
                        $error,
                        $payload,
                        $attachments
                    );
                } else {
                    do_action(
                        'forms_bridge_after_submission',
                        $bridge,
                        $payload,
                        $attachments,
                        $response
                    );
                }
            } catch (Error | Exception $e) {
                $message = $e->getMessage();
                if (strstr($message, 'Error while submitting form ')) {
                    throw $e;
                }

                $error = new WP_Error(
                    'internal_server_error',
                    $e->getMessage(),
                    $e->getTrace()
                );

                do_action(
                    'forms_bridge_on_failure',
                    $bridge,
                    $error,
                    $payload ?? $submission,
                    $attachments ?? []
                );
            }
        }
    }

    /**
     * Clean up submission empty fields.
     *
     * @param array $submission_data Submission data.
     *
     * @return array Submission data without empty fields.
     */
    private static function prune_empties($submission_data)
    {
        foreach ($submission_data as $key => $val) {
            if ($val === '' || $val === null) {
                unset($submission_data[$key]);
            }
        }

        return $submission_data;
    }

    /**
     * Transform collection of uploads to an attachments map.
     *
     * @param array $uploads Collection of uploaded files.
     *
     * @return array Map of uploaded files.
     */
    private static function attachments($uploads)
    {
        return array_reduce(
            array_keys($uploads),
            function ($carry, $name) use ($uploads) {
                if ($uploads[$name]['is_multi']) {
                    for ($i = 1; $i <= count($uploads[$name]['path']); $i++) {
                        $carry[$name . '_' . $i] =
                            $uploads[$name]['path'][$i - 1];
                    }
                } else {
                    $carry[$name] = $uploads[$name]['path'];
                }

                return $carry;
            },
            []
        );
    }

    /**
     * Returns the attachments array with each attachment path replaced with its
     * content as a base64 encoded string. For each file on the list, adds an
     * additonal field with the file name on the response.
     *
     * @param array $attachments Submission attachments data.
     *
     * @return array Array with base64 encoded file contents and file names.
     */
    private static function stringify_attachments($attachments)
    {
        foreach ($attachments as $name => $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $filename = basename($path);
            $content = file_get_contents($path);
            $attachments[$name] = base64_encode($content);
            $attachments[$name . '_filename'] = $filename;
        }

        return $attachments;
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

    /**
     * Apply db migrations on plugin upgrades.
     */
    private static function do_migrations()
    {
        $from = get_option('forms-bridge-version', '1.0.0');

        if (!preg_match('/^\d+\.\d+\.\d+$/', $from)) {
            Logger::log('Invalid db plugin version', Logger::ERROR);
            return;
        }

        $to = self::version();

        $migrations = [];
        $migrations_path = self::path() . 'migrations';

        $as_int = fn($version) => (int) str_replace('.', '', $version);

        foreach (
            array_diff(scandir($migrations_path), ['.', '..'])
            as $migration
        ) {
            $version = pathinfo($migrations_path . '/' . $migration)[
                'filename'
            ];

            if ($as_int($version) > $as_int($to)) {
                break;
            }

            if (!empty($migrations)) {
                $migrations[] = $migration;
                continue;
            }

            if ($as_int($version) >= $as_int($from)) {
                $migrations[] = $migration;
            }
        }

        sort($migrations);
        foreach ($migrations as $migration) {
            include $migrations_path . '/' . $migration;
        }

        update_option('forms-bridge-version', $to);
    }
}

// Start the plugin
Forms_Bridge::setup();
