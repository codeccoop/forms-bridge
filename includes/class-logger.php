<?php

namespace FORMS_BRIDGE;

use WP_Error;
use WP_REST_Server;
use WPCT_ABSTRACT\Singleton;

if (!defined('ABSPATH')) {
    exit();
}

class Logger extends Singleton
{
    private const log_file = '.forms-bridge.log';

    private static function log_path()
    {
        return wp_upload_dir()['basedir'] . '/' . self::log_file;
    }

    private static function logs()
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

        return file_get_contents($log_path);
    }

    public static function is_active()
    {
        $log_path = self::log_path();
        return is_file($log_path);
    }

    public static function activate()
    {
        if (!self::is_active()) {
            $log_path = self::log_path();
            if (!is_file($log_path)) {
                touch($log_path);
            }
        }
    }

    public static function deactivate()
    {
        if (self::is_active()) {
            $log_path = self::log_path();
            if (is_file($log_path)) {
                wp_delete_file($log_path);
            }
        }
    }

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

    public static function lines($offset = 0, $len = null)
    {
        $logs = self::logs();
        $lines = preg_split('/\n+/', $logs);

        $offset = (int) $offset;
        if ($offset < 0) {
            $len = abs($offset);
            $offset = count($lines) - $len;
        } else {
            $len = is_int($len) ? $len : count($lines) - $offset;
        }
        return array_values(array_filter(array_slice($lines, $offset, $len)));
    }

    protected function construct(...$args)
    {
        add_action('rest_api_init', function () {
            $this->register_log_route();
        });

        add_filter(
            'wpct_setting_default',
            function ($default, $name) {
                if ($name !== Forms_Bridge::slug() . '_general') {
                    return $default;
                }

                return array_merge($default, ['debug' => self::is_active()]);
            },
            5,
            2
        );

        add_action('plugins_loaded', function () {
            $plugin_slug = Forms_Bridge::slug();
            add_filter("option_{$plugin_slug}_general", function ($value) {
                $value['debug'] = self::is_active();
                return $value;
            });
        });

        add_filter(
            'wpct_validate_setting',
            function ($data, $setting) {
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

    private function register_log_route()
    {
        register_rest_route('wp-bridges/v1', '/forms-bridge/logs/', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => function () {
                return self::lines(-500);
            },
            'permission_callback' => function () {
                return $this->permission_callback();
            },
        ]);
    }

    private function permission_callback()
    {
        return current_user_can('manage_options')
            ? true
            : new WP_Error(
                'rest_unauthorized',
                'You can\'t manage wp options',
                ['status' => 403]
            );
    }
}

Logger::setup();
