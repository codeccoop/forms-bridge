<?php

namespace FORMS_BRIDGE;

use WP_Error;
use WP_REST_Server;
use WPCT_ABSTRACT\Singleton;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Admin logger class.
 */
class Logger extends Singleton
{
    /**
     * Handles the log file name.
     */
    private const log_file = '.forms-bridge.log';

    /**
     * Error level constant.
     *
     * @var string
     */
    public const ERROR = 'ERROR';

    /**
     * Info level constant.
     *
     * @var string
     */
    public const INFO = 'INFO';

    /**
     * Debug level constant.
     *
     * @var string
     */
    public const DEBUG = 'DEBUG';

    /**
     * Log file path getter.
     */
    private static function log_path()
    {
        return wp_upload_dir()['basedir'] . '/' . self::log_file;
    }

    /**
     * Gets the log file contents.
     *
     * @return string Logs.
     */
    private static function logs($lines = 500)
    {
        if (
            defined('WP_DEBUG') &&
            WP_DEBUG &&
            defined('WP_DEBUG_LOG') &&
            (bool) WP_DEBUG_LOG
        ) {
            $log_path = ini_get('error_log');
        } else {
            $log_path = self::log_path();
        }

        if (!is_file($log_path) || !is_readable($log_path)) {
            return '';
        }

        $socket = fopen($log_path, 'r');
        $cursor = -1;
        fseek($socket, $cursor, SEEK_END);

        $char = fgetc($socket);
        while ($char === "\n" || $char === "\r") {
            fseek($socket, $cursor--, SEEK_END);
            $char = fgetc($socket);
        }

        $line = 0;
        $content = '';

        while ($line < $lines) {
            if ($char === false) {
                break;
            }

            while ($char !== "\n" && $char !== "\r") {
                if ($char === false) {
                    break;
                }

                fseek($socket, $cursor--, SEEK_END);
                $char = fgetc($socket);
                $content = $char . $content;
            }

            while ($char === "\n" || $char === "\r") {
                fseek($socket, $cursor--, SEEK_END);
                $char = fgetc($socket);
            }

            $content = $char . $content;
            $line++;
        }

        fclose($socket);

        return (array) preg_split('/(\n|\r)+/', $content);
    }

    /**
     * Write log lines to the log file if debug mode is active.
     *
     * @param mixed $data Log line data.
     * @param string $level Log level, DEBUG as default.
     */
    public static function log($data, $level = 'DEBUG')
    {
        if (!self::is_active()) {
            return;
        }

        if (!in_array($level, ['DEBUG', 'ERROR', 'INFO'], true)) {
            $level = 'DEBUG';
        }

        $msg = sprintf("[%s] %s\n", $level, print_r($data, true));

        $socket = fopen(self::log_path(), 'a+');
        fwrite($socket, $msg, strlen($msg));
        fclose($socket);
    }

    /**
     * Check if the debug mode is active.
     *
     * @return boolean
     */
    public static function is_active()
    {
        $log_path = self::log_path();
        return is_file($log_path);
    }

    /**
     * Debug mode activator.
     */
    public static function activate()
    {
        if (!self::is_active()) {
            $log_path = self::log_path();
            if (!is_file($log_path)) {
                touch($log_path);
            }
        }
    }

    /**
     * Debug mode deactivator.
     */
    public static function deactivate()
    {
        if (self::is_active()) {
            $log_path = self::log_path();
            if (is_file($log_path)) {
                wp_delete_file($log_path);
            }
        }
    }

    /**
     * Logger's setup method. Initializes php log configurations.
     */
    public static function setup()
    {
        if (self::is_active()) {
            error_reporting(E_ALL);
            if (!ini_get('log_errors')) {
                ini_set('log_errors', 1);
            }

            if (!defined('WP_DEBUG_LOG') || !boolval(WP_DEBUG_LOG)) {
                ini_set('error_log', self::log_path());
            }
        }

        return self::get_instance();
    }

    /**
     * Logger singleton constructor. Binds the logger to wp and custom hooks
     */
    protected function construct(...$args)
    {
        add_action('rest_api_init', static function () {
            self::register_log_route();
        });

        add_filter(
            'wpct_setting_default',
            static function ($default, $name) {
                if ($name !== Forms_Bridge::slug() . '_general') {
                    return $default;
                }

                return array_merge($default, ['debug' => self::is_active()]);
            },
            5,
            2
        );

        add_action('plugins_loaded', static function () {
            $plugin_slug = Forms_Bridge::slug();
            add_filter("option_{$plugin_slug}_general", static function (
                $value
            ) {
                if (!is_array($value)) {
                    return $value;
                }

                $value['debug'] = self::is_active();
                return $value;
            });
        });

        add_filter(
            'wpct_validate_setting',
            static function ($data, $setting) {
                if (
                    $setting->full_name() !==
                    Forms_Bridge::slug() . '_general'
                ) {
                    return $data;
                }

                if (isset($data['debug']) && $data['debug'] === true) {
                    self::activate();
                } else {
                    self::deactivate();
                }

                unset($data['debug']);
                return $data;
            },
            9,
            2
        );
    }

    /**
     * Registers the logger REST API route.
     */
    private static function register_log_route()
    {
        register_rest_route('forms-bridge/v1', '/logs/', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => static function () {
                $lines = isset($_GET['lines']) ? (int) $_GET['lines'] : 500;
                $logs = self::logs($lines);

                if (empty($logs)) {
                    return [];
                }

                return $logs;
            },
            'permission_callback' => static function () {
                return self::permission_callback();
            },
        ]);
    }

    /**
     * REST API route's permission callback.
     *
     * @return boolean|WP_Error
     */
    private static function permission_callback()
    {
        return current_user_can('manage_options') ?:
            new WP_Error('rest_unauthorized', 'You can\'t manage wp options', [
                'status' => 403,
            ]);
    }
}

Logger::setup();
