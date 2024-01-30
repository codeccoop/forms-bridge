<?php

namespace WPCT_ERP_FORMS;

class Field
{
    private static $instances = [];

    public static function get_instance()
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }

    public function register()
    {
        throw new Exception('Method to overwrite by inheritance');
    }
}
