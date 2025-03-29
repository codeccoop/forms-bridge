<?php

namespace FORMS_BRIDGE;

use TypeError;
use Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * JSON Finger handler.
 */
class JSON_Finger
{
    /**
     * Handle target array data.
     *
     * @var array $data Target array data.
     */
    private $data;

    private static $cache = [];

    /**
     * Parse a json finger pointer and returns it as an array of keys.
     *
     * @param string $pointer JSON finger pointer.
     *
     * @return array Array with finger keys.
     */
    public static function parse($pointer)
    {
        $pointer = (string) $pointer;

        if (isset(self::$cache[$pointer])) {
            return self::$cache[$pointer];
        }

        $len = strlen($pointer);
        $keys = [];
        $key = '';

        for ($i = 0; $i < $len; $i++) {
            $char = $pointer[$i];

            if ($char === '.') {
                if (strlen($key)) {
                    $keys[] = $key;
                    $key = '';
                }
            } elseif ($char === '[') {
                if (strlen($key)) {
                    $keys[] = $key;
                    $key = '';
                }

                $i = $i + 1;
                while ($pointer[$i] !== ']' && $i < $len) {
                    $key .= $pointer[$i];
                    $i += 1;
                }

                if (strlen($key) === 0) {
                    self::$cache[$pointer] = [];
                    return [];
                } elseif (intval($key) != $key) {
                    if (!preg_match('/^"[^"]+"$/', $key, $matches)) {
                        self::$cache[$pointer] = [];
                        return [];
                    }

                    $key = json_decode($key);
                } else {
                    $key = (int) $key;
                }

                $keys[] = $key;
                $key = '';

                if (strlen($pointer) - 1 > $i) {
                    if ($pointer[$i + 1] !== '.' && $pointer[$i + 1] !== '[') {
                        self::$cache[$pointer] = [];
                        return [];
                    }
                }
            } else {
                $key .= $char;
            }
        }

        if ($key) {
            $keys[] = $key;
        }

        self::$cache[$pointer] = $keys;
        return $keys;
    }

    public static function sanitizeKey($key)
    {
        if (intval($key) === $key) {
            $key = "[{$key}]";
        } else {
            $key = trim($key);

            if (
                preg_match('/( |\.|")/', $key) &&
                !preg_match('/^\["[^"]+"\]$/', $key)
            ) {
                $key = "[\"{$key}\"]";
            }
        }

        return $key;
    }

    public static function validate($pointer): bool
    {
        $pointer = (string) $pointer;

        if (!strlen($pointer)) {
            return false;
        }

        return count(self::parse($pointer)) > 0;
    }

    public static function pointer($keys)
    {
        if (!is_array($keys)) {
            return '';
        }

        return array_reduce(
            $keys,
            static function ($pointer, $key) {
                $is_array = intval($key) === $key;
                if ($is_array) {
                    $key = "[{$key}]";
                } else {
                    $key = self::sanitizeKey($key);

                    if ($key[0] !== '[' && strlen($pointer) > 0) {
                        $key = '.' . $key;
                    }
                }

                return $pointer . $key;
            },
            ''
        );
    }

    /**
     * Binds data to the handler instance.
     *
     * @param array $data Target data.
     */
    public function __construct($data)
    {
        if (!is_array($data)) {
            throw new TypeError('Input data isn\'t an array');
        }

        $this->data = $data;
    }

    /**
     * Proxy handler attributes to the data.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
    }

    /**
     * Proxy handler attribute updates to the data.
     *
     * @param string $name Attribute name.
     * @param mixed $value Attribute value.
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Returns de current data.
     *
     * @return array Current data.
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Gets the attribute from the data.
     *
     * @param string $pointer JSON finger pointer.
     * pointer.
     *
     * @return mixed Attribute value.
     */
    public function get($pointer)
    {
        $pointer = (string) $pointer;

        if ($this->$pointer) {
            return $this->$pointer;
        }

        $value = null;
        try {
            $keys = self::parse($pointer);

            $value = $this->data;
            foreach ($keys as $key) {
                if (!isset($value[$key])) {
                    return;
                }

                $value = $value[$key];
            }
        } catch (Error) {
            return;
        }

        return $value;
    }

    /**
     * Sets the attribute value on the data.
     *
     * @param string $pointer JSON finger pointer.
     * @param mixed $value Attribute value.
     * @param boolean $unset If true, unsets the attribute.
     *
     * @return array Data after the attribute update.
     */
    public function set($pointer, $value, $unset = false)
    {
        if ($this->$pointer) {
            $this->$pointer = $value;
            return $this->data;
        }

        $data = $this->data;
        $breadcrumb = [];

        try {
            $keys = self::parse($pointer);
            $partial = &$data;

            for ($i = 0; $i < count($keys) - 1; $i++) {
                if (!is_array($partial)) {
                    return $data;
                }

                $key = $keys[$i];
                if (is_int($key)) {
                    if (!wp_is_numeric_array($partial)) {
                        return $data;
                    }
                }

                if (!isset($partial[$key])) {
                    $partial[$key] = [];
                }

                $breadcrumb[] = ['partial' => &$partial, 'key' => $key];
                $partial = &$partial[$key];
            }

            $key = $keys[$i];
            if ($unset) {
                if (wp_is_numeric_array($partial)) {
                    array_splice($partial, $key, 1);
                } elseif (is_array($partial)) {
                    unset($partial[$key]);
                }

                for ($i = count($breadcrumb) - 1; $i >= 0; $i--) {
                    $step = $breadcrumb[$i];
                    $partial = &$step['partial'];
                    $key = $step['key'];

                    if (!empty($partial[$key])) {
                        break;
                    }

                    if (wp_is_numeric_array($partial)) {
                        array_splice($partial, $key, 1);
                    } else {
                        unset($partial[$key]);
                    }
                }
            } else {
                $partial[$key] = $value;
            }
        } catch (Error) {
            return $data;
        }

        $this->data = $data;
        return $data;
    }

    /**
     * Unsets the attribute from the data.
     *
     * @param string $pointer JSON finger pointer.
     */
    public function unset($pointer)
    {
        if (isset($this->data[$pointer])) {
            if (intval($pointer) === $pointer) {
                if (wp_is_numeric_array($this->data)) {
                    array_splice($this->data, $pointer, 1);
                }
            } else {
                unset($this->data[$pointer]);
            }

            return $this->data;
        }

        return $this->set($pointer, null, true);
    }

    /**
     * Checks if the json finger is set on the data.
     *
     * @param string $pointer JSON finger pointer.
     *
     * @return boolean True if attribute is set.
     */
    public function isset($pointer)
    {
        $keys = self::parse($pointer);

        switch (count($keys)) {
            case 0:
                return false;
            case 1:
                $key = $keys[0];
                return isset($this->data[$key]);
            default:
                $key = array_pop($keys);
                $pointer = self::pointer($keys);
                $parent = $this->get($pointer);
                return isset($parent[$key]);
        }
    }
}
