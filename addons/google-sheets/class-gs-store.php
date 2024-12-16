<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\Singleton;

if (!defined('ABSPATH')) {
    exit();
}

if (!defined('FORMS_BRIDGE_GS_STORE_SECRET')) {
    define('FORMS_BRIDGE_GS_STORE_SECRET', 'forms-bridge-store-secret');
}

class Google_Sheets_Store extends Singleton
{
    public $path;

    public static function setup()
    {
        return self::get_instance();
    }

    public static function store()
    {
        return self::get_instance();
    }

    public static function get($path)
    {
        $path = self::store()->store_path($path);
        if (!is_file($path)) {
            return null;
        }

        return self::store()->decrypt(file_get_contents($path));
    }

    public static function set($path, $content)
    {
        $path = self::store()->store_path($path);
        file_put_contents($path, self::store()->encrypt($content));
    }

    public static function delete($path)
    {
        $path = self::store()->store_path($path);
        if (is_file($path)) {
            unlink($path);
        }
    }

    protected function construct(...$args)
    {
        $this->path = plugin_dir_path(__FILE__) . '.store';

        if (!is_dir($this->path)) {
            mkdir($this->path, 0700);
        }

        $htaccess = "{$this->path}/.htaccess";
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, 'Deny from all');
        }
    }

    private function secret($len)
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

    public function store_path($file)
    {
        $store_path = self::store()->path;
        $file = preg_replace('/^\//', '', $file);
        return "{$store_path}/{$file}";
    }

    public function encrypt($value)
    {
        $secret = $this->secret(strlen($value));
        return $value ^ $secret;
    }

    public function decrypt($value)
    {
        return self::encrypt($value);
    }
}

Google_Sheets_Store::setup();
