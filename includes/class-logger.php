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
    private const config_path = ABSPATH . 'wp-config.php';
    private const log_path = ABSPATH . 'wp-content/debug.log';
    private const debug_re = '/(?<=define\()\s*.WP_DEBUG.\s*,\s*(true|false|[0-1])\s*(?=\))/i';
    private const log_re = '/(?<=define\()\s*.WP_DEBUG_LOG.\s*,\s*(true|false|[0-1])\s*(?=\))/i';
    private const display_re = '/(?<=define\()\s*.WP_DEBUG_DISPLAY.\s*,\s*(true|false|[0-1])\s*(?=\))/i';

    private const custom_block_closure = '/* That\'s all, stop editing! Happy publishing. */';

    private static function config($config = null)
    {
        if (is_string($config)) {
            file_put_contents(self::config_path, $config);
        }

        return file_get_contents(self::config_path);
    }

    private static function logs($lines = null)
    {
        if (is_string($lines)) {
            file_put_contents(self::log_path, $lines);
        }

        if (!is_file(self::log_path)) {
            $fp = fopen(self::log_path, 'w');
            fclose($fp);
            chmod(self::log_path, 0600);
        }

        return file_get_contents(self::log_path);
    }

    public static function is_active()
    {
        $config = self::config();
        preg_match(self::debug_re, $config, $matches);
        return empty($matches)
            ? false
            : strtolower($matches[1]) === 'true' || $matches[1] === '1';
    }

    public static function activate()
    {
        $config = self::config();
        $config = preg_replace(self::debug_re, '\'WP_DEBUG\', true', $config);
        $config = preg_replace(self::log_re, '\'WP_DEBUG_LOG\', true', $config);
        $config = preg_replace(
            self::display_re,
            '\'WP_DEBUG_DISPLAY\', false',
            $config
        );
        self::config($config);
    }

    public static function deactivate()
    {
        $config = self::config();
        $config = preg_replace(self::debug_re, '\'WP_DEBUG\', false', $config);
        $config = preg_replace(
            self::log_re,
            '\'WP_DEBUG_LOG\', false',
            $config
        );
        self::config($config);
        self::logs('');
    }

    public static function setup()
    {
        $config = self::config();

        $debug_const = preg_match(self::debug_re, $config);
        if (!$debug_const) {
            $config = preg_replace(
                '/' . preg_quote(self::custom_block_closure, '/') . '/',
                "define('WP_DEBUG', false);\n" . self::custom_block_closure,
                $config
            );
            $config = self::config($config);
        }

        $log_const = preg_match(self::log_re, $config);
        if (!$log_const) {
            $config = preg_replace(
                '/' . preg_quote(self::custom_block_closure, '/') . '/',
                "define('WP_DEBUG_LOG', false);\n" . self::custom_block_closure,
                $config
            );
            $config = self::config($config);
        }

        $display_const = preg_match(self::display_re, $config);
        if (!$display_const) {
            $config = preg_replace(
                '/' . preg_quote(self::custom_block_closure, '/') . '/',
                "define('WP_DEBUG_DISPLAY', false);\n" .
                    self::custom_block_closure,
                $config
            );
            $config = self::config($config);
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
            'pre_update_option',
            function ($value, $option) {
                if ($option !== Forms_Bridge::slug() . '_general') {
                    return $value;
                }

                if (isset($value['debug']) && $value['debug'] === true) {
                    self::activate();
                } else {
                    self::deactivate();
                }

                unset($value['debug']);
                return $value;
            },
            5,
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
