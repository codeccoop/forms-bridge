<?php

/*
 * Plugin Name:         Forms Bridge
 * Plugin URI:          https://formsbridge.codeccoop.org
 * Description:         Plugin to bridge WP forms submissions to any backend or service
 * Author:              codeccoop
 * Author URI:          https://www.codeccoop.org
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         forms-bridge
 * Domain Path:         /languages
 * Version:             3.5.4
 * Requires PHP:        8.0
 * Requires at least:   6.7
 */

namespace FORMS_BRIDGE;

use Error;
use Exception;
use WP_Error;
use WPCT_PLUGIN\Plugin as Base_Plugin;
use FBAPI;

if (!defined('ABSPATH')) {
    exit();
}

define('FORMS_BRIDGE_INDEX', __FILE__);
define('FORMS_BRIDGE_DIR', dirname(__FILE__));
define('FORMS_BRIDGE_INTEGRATIONS_DIR', FORMS_BRIDGE_DIR . '/integrations');
define('FORMS_BRIDGE_ADDONS_DIR', FORMS_BRIDGE_DIR . '/addons');

// Commons
require_once 'common/class-plugin.php';

// Deps
require_once 'deps/http/http-bridge.php';
require_once 'deps/i18n/wpct-i18n.php';

// Traits
require_once 'includes/trait-bridge-custom-fields.php';
require_once 'includes/trait-bridge-mutations.php';

// Classes
require_once 'includes/class-api.php';
require_once 'includes/class-json-finger.php';
require_once 'includes/class-rest-settings-controller.php';
require_once 'includes/class-settings-store.php';
require_once 'includes/class-logger.php';
require_once 'includes/class-menu.php';
require_once 'includes/class-form-bridge.php';
require_once 'includes/class-form-bridge-template.php';
require_once 'includes/class-job.php';
require_once 'includes/class-credential.php';
require_once 'includes/class-integration.php';
require_once 'includes/class-addon.php';

// Post types
require_once 'post_types/job.php';
require_once 'post_types/bridge-template.php';

/**
 * Forms Bridge plugin.
 */
class Forms_Bridge extends Base_Plugin
{
    /**
     * Handles the plugin db version option name.
     *
     * @var string
     */
    private const db_version = 'forms-bridge-version';

    /**
     * Handles plugin settings class name.
     *
     * @var string
     */
    protected const store_class = '\FORMS_BRIDGE\Settings_Store';

    /**
     * Handle plugin menu class name.
     *
     * @var string
     */
    protected const menu_class = '\FORMS_BRIDGE\Menu';

    /**
     * Handles the current bridge instance. Available only during form submissions.
     *
     * @var Form_Bridge|null
     */
    private static $current_bridge;

    /**
     * Initializes integrations, addons and setup plugin hooks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        Addon::load_addons();
        Integration::load_integrations();

        add_action('admin_enqueue_scripts', static function ($admin_page) {
            if ('settings_page_forms-bridge' === $admin_page) {
                self::admin_enqueue_scripts();
            }
        });

        add_filter(
            'plugin_action_links',
            static function ($links, $file) {
                if ($file !== 'forms-bridge/forms-bridge.php') {
                    return $links;
                }

                $url = 'https://formsbridge.codeccoop.org/documentation/';
                $label = __('Documentation', 'forms-bridge');
                $link = sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    esc_url($url),
                    esc_html($label)
                );
                array_push($links, $link);

                return $links;
            },
            15,
            2
        );

        add_action(
            'forms_bridge_on_failure',
            static function ($bridge, $error, $payload, $attachments = []) {
                self::notify_error($bridge, $error, $payload, $attachments);
            },
            99,
            4
        );

        add_action('init', [self::class, 'load_data'], 0, 0);
    }

    /**
     * Plugin activation callback. Stores the plugin version on the database
     * if it doesn't exists.
     */
    public static function activate()
    {
        $version = get_option(self::db_version);
        if ($version === false) {
            update_option(self::db_version, self::version(), true);
        }
    }

    /**
     * Init hook callabck. Checks if the stored db version mismatch the current plugin version
     * and, if it is, performs db migrations.
     */
    protected static function init()
    {
        $db_version = get_option(self::db_version);
        if ($db_version !== self::version()) {
            self::do_migrations();
        }
    }

    /**
     * Data loader.
     */
    public static function load_data()
    {
        $data_dir = self::path() . 'data';
        foreach (array_diff(scandir($data_dir), ['.', '..']) as $file) {
            $filepath = "{$data_dir}/{$file}";
            if (is_file($filepath) && is_readable($filepath)) {
                require_once $filepath;
            }
        }
    }

    /**
     * Enqueue admin client scripts
     */
    private static function admin_enqueue_scripts()
    {
        $version = self::version();

        // wp_enqueue_script(
        //     'forms-bridge',
        //     plugins_url('assets/wpfb.js', __FILE__),
        //     [],
        //     $version,
        //     ['in_footer' => false]
        // );

        wp_enqueue_script(
            'forms-bridge',
            plugins_url('assets/plugin.bundle.js', __FILE__),
            [
                // 'forms-bridge',
                'react',
                'react-jsx-runtime',
                'wp-api-fetch',
                'wp-components',
                'wp-dom-ready',
                'wp-element',
                'wp-i18n',
                'wp-api',
            ],
            $version,
            ['in_footer' => true]
        );

        wp_set_script_translations(
            'forms-bridge',
            'forms-bridge',
            self::path() . 'languages'
        );

        wp_enqueue_style('wp-components');

        wp_enqueue_style(
            'highlight-js',
            'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github.min.css',
            [],
            '11.11.1'
        );

        wp_enqueue_script(
            'highlight-js',
            'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js',
            [],
            '11.11.1'
        );
    }

    public static function current_bridge()
    {
        return self::$current_bridge;
    }

    /**
     * Proceed with the submission sub-routine.
     */
    public static function do_submission()
    {
        $form_data = FBAPI::get_current_form();

        if (!$form_data) {
            return;
        }

        if (empty($form_data['bridges'])) {
            return;
        }

        Logger::log('Form data');
        Logger::log([
            'id' => $form_data['id'],
            'title' => $form_data['title'],
            'fields' => array_map(function ($field) {
                return $field['name'];
            }, $form_data['fields']),
        ]);

        $bridges = $form_data['bridges'];

        $submission = FBAPI::get_submission();
        Logger::log('Form submission');
        Logger::log($submission);

        $uploads = FBAPI::get_uploads();
        Logger::log('Submission uploads');
        Logger::log($uploads);

        if (empty($submission) && empty($uploads)) {
            return;
        }

        foreach ($bridges as $bridge) {
            if (!$bridge->is_valid) {
                Logger::log(
                    'Skip submission for invalid bridge ' . $bridge->name
                );
                continue;
            } elseif (!$bridge->enabled) {
                Logger::log(
                    'Skip submission for disabled bridge ' . $bridge->name
                );
                continue;
            }

            self::$current_bridge = $bridge;

            try {
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
                            $attachments,
                            $bridge,
                            $uploads
                        );
                        foreach ($attachments as $name => $value) {
                            $submission[$name] = $value;
                        }
                        $attachments = [];
                        Logger::log('Submission after attachments stringify');
                        Logger::log($submission);
                    }
                }

                $payload = $bridge->add_custom_fields($submission);
                Logger::log('Submission payload with bridge custom fields');
                Logger::log($payload);

                $bridge->setup_conditional_mappers($form_data);
                $payload = $bridge->apply_mutation($payload);
                Logger::log('Submission payload after mutation');
                Logger::log($payload);

                $prune_empties = apply_filters(
                    'forms_bridge_prune_empties',
                    true,
                    $bridge
                );

                if ($prune_empties) {
                    $payload = self::prune_empties($payload);
                    Logger::log('Submission payload after prune empties');
                    Logger::log($payload);
                }

                if ($job = $bridge->workflow) {
                    $payload = $job->run($payload, $bridge);

                    if (empty($payload)) {
                        Logger::log('Skip empty payload after bridge workflow');
                        continue;
                    }

                    Logger::log('Payload after workflow');
                    Logger::log($payload);
                }

                $payload = apply_filters(
                    'forms_bridge_payload',
                    $payload,
                    $bridge
                );

                if (empty($payload)) {
                    Logger::log('Skip empty payload after user filter');
                    continue;
                }

                Logger::log('Bridge payload');
                Logger::log($payload);

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

                if ($error = is_wp_error($response) ? $response : null) {
                    do_action(
                        'forms_bridge_on_failure',
                        $bridge,
                        $error,
                        $payload,
                        $attachments
                    );
                } else {
                    Logger::log('Submission response');
                    Logger::log($response);

                    do_action(
                        'forms_bridge_after_submission',
                        $bridge,
                        $response,
                        $payload,
                        $attachments
                    );
                }
            } catch (Error | Exception $e) {
                $message = $e->getMessage();
                if ($message === 'notification_error') {
                    throw $e;
                }

                $error = new WP_Error(
                    'internal_server_error',
                    $e->getMessage(),
                    [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                );

                do_action(
                    'forms_bridge_on_failure',
                    $bridge,
                    $error,
                    $payload ?? $submission,
                    $attachments ?? []
                );
            } finally {
                self::$current_bridge = null;
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
    private static function stringify_attachments(
        $attachments,
        $bridge,
        $uploads
    ) {
        foreach ($attachments as $name => $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $filename = basename($path);
            $content = file_get_contents($path);
            $attachments[$name] = base64_encode($content);
            $attachments[$name . '_filename'] = $filename;
        }

        $mutation = $bridge->mutations[0] ?? [];
        foreach ($mutation as &$mapper) {
            $mapper['from'] = '?' . $mapper['from'];
        }

        $attachments = $bridge->apply_mutation($attachments, $mutation);

        foreach ($attachments as $field => $value) {
            if (isset($uploads[$field])) {
                continue;
            }

            if (strstr($field, '_filename')) {
                $unique_field = preg_replace('/_\d+(?=_filename)/', '', $field);
            } else {
                $unique_field = preg_replace('/(?<=_)\d+$/', '', $field);
            }

            if ($unique_field === $field) {
                continue;
            }

            $value = $attachments[$field];
            unset($attachments[$field]);

            $attachment = $bridge->apply_mutation(
                [$unique_field => $value],
                $mutation
            );

            if (!empty($attachment)) {
                $attachments[$field] = $attachment[$unique_field];
            }
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
        $attachments = []
    ) {
        $email = Settings_Store::setting('general')->notification_receiver;

        if (empty($email)) {
            return;
        }

        $skip = apply_filters(
            'forms_bridge_skip_error_notification',
            false,
            $error,
            $bridge,
            $payload,
            $attachments
        );

        if ($skip) {
            Logger::log('Skip error notification');
            return;
        }

        $form_data = $bridge->form;
        $payload = json_encode($payload, JSON_PRETTY_PRINT);
        $error = json_encode(
            [
                'error' => $error->get_error_message(),
                'context' => $error->get_error_data(),
            ],
            JSON_PRETTY_PRINT
        );

        Logger::log('Bridge submission error', Logger::ERROR);
        Logger::log($error, Logger::ERROR);

        $to = $email;
        $subject = 'Forms Bridge Error';
        $body = "Form ID: {$form_data['id']}\n";
        $body .= "Form title: {$form_data['title']}\n";
        $body .= "Bridge name: {$bridge->name}\n";
        $body .= "Payload: {$payload}\n";
        $body .= "Error: {$error}\n";

        $from_email = get_option('admin_email');
        $headers = ["From: Forms Bridge <{$from_email}>"];

        Logger::log('Error notification');
        Logger::log($body);

        $success = wp_mail($to, $subject, $body, $headers, $attachments);
        if (!$success) {
            throw new Exception('notification_error');
        }
    }

    /**
     * Apply db migrations on plugin upgrades.
     */
    private static function do_migrations()
    {
        $from = get_option(self::db_version, '1.0.0');

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

        update_option(self::db_version, $to);
    }
}

// Start the plugin
Forms_Bridge::setup();
