<?php

namespace FORMS_BRIDGE;

use WPCT_PLUGIN\Singleton;

if (!defined('ABSPATH')) {
    exit();
}

if (!defined('FORMS_BRIDGE_GS_STORE_SECRET')) {
    define('FORMS_BRIDGE_GS_STORE_SECRET', 'wp-bridge-store-secret');
}

class Google_Sheets_Store extends Singleton
{
    private const store_name = 'forms_bridge_gs_store';

    private static $cache = null;

    public static function setup()
    {
        return self::get_instance();
    }

    public static function store()
    {
        return self::get_instance();
    }

    public static function get($name)
    {
        return self::load($name);
    }

    public static function set($name, $content)
    {
        self::save($name, $content);
    }

    public static function delete($name)
    {
        self::forget($name);
    }

    protected function construct(...$args)
    {
        add_action(
            'updated_option',
            static function ($opt, $from, $to) {
                if ($opt === self::store_name) {
                    self::$cache = $to;
                }
            },
            5,
            3
        );

        add_action(
            'deleted_option',
            static function ($opt) {
                if ($opt === self::store_name) {
                    self::$cache = null;
                }
            },
            5
        );
    }

    private static function secret($len)
    {
        $secret = substr(FORMS_BRIDGE_GS_STORE_SECRET, 0, $len);

        while (strlen($secret) < $len) {
            $secret .= substr(
                FORMS_BRIDGE_GS_STORE_SECRET,
                0,
                $len - strlen(FORMS_BRIDGE_GS_STORE_SECRET)
            );
        }

        return $secret;
    }

    private static function data()
    {
        if (empty(self::$cache)) {
            self::$cache = (array) get_option(self::store_name, []);
        }

        return self::$cache;
    }

    private static function load($name)
    {
        $_name = self::encrypt($name);
        $data = self::data();
        return isset($data[$_name]) ? self::decrypt($data[$_name]) : null;
    }

    private static function save($name, $content)
    {
        $_name = self::encrypt($name);
        $_content = self::encrypt((string) $content);
        $data = self::data();
        update_option(
            self::store_name,
            array_merge($data, [$_name => $_content])
        );
    }

    private static function forget($name)
    {
        $_name = self::encrypt($name);
        $data = self::data();
        if (isset($data[$_name])) {
            unset($data[$_name]);
            update_option(self::store_name, $data);
        }
    }

    private static function encrypt($value)
    {
        $secret = self::secret(strlen($value));
        return $value ^ $secret;
    }

    private static function decrypt($value)
    {
        return self::encrypt($value);
    }
}

Google_Sheets_Store::setup();
