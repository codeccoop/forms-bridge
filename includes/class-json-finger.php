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

    /**
     * Handles a register of parsed pointers as a memory cache to reduce pointer
     * parsing operations.
     *
     * @var array
     */
    private static $cache = [];

    /**
     * Parse a json finger pointer and returns it as an array of keys.
     *
     * @param string $pointer JSON finger pointer.
     * @param boolean &$is_conditional Reference to handle if the parsed pointer is
     * conditional.
     *
     * @return array Array with finger keys.
     */
    public static function parse($pointer, &$is_conditional = false)
    {
        $pointer = (string) $pointer;

        $is_conditional = strpos($pointer, '?') === 0;
        if ($is_conditional) {
            $pointer = substr($pointer, 1);
        }

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
                    $key = INF;
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

    /**
     * Sanitize a key to be a valid finger key.
     *
     * @param string|int Finger key value.
     *
     * @return string Sanitized key value.
     */
    public static function sanitize_key($key)
    {
        if ($key === INF) {
            $key = '[]';
        } elseif (intval($key) == $key) {
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

    /**
     * Validates the finger pointer.
     *
     * @param string $pointer Finger pointer.
     *
     * @return boolean Validation result.
     */
    public static function validate($pointer)
    {
        $pointer = (string) $pointer;

        if (!strlen($pointer)) {
            return false;
        }

        return count(self::parse($pointer)) > 0;
    }

    /**
     * Returns a finger pointer from an array of keys after keys validation and sanitization.
     *
     * @param array $keys Array with finger keys.
     *
     * @return string Finger pointer result.
     */
    public static function pointer($keys, $is_conditional = false)
    {
        if (!is_array($keys)) {
            return '';
        }

        $pointer = array_reduce(
            $keys,
            static function ($pointer, $key) {
                if ($key === INF) {
                    $key = '[]';
                } elseif (intval($key) == $key) {
                    $key = "[{$key}]";
                } else {
                    $key = self::sanitize_key($key);

                    if ($key[0] !== '[' && strlen($pointer) > 0) {
                        $key = '.' . $key;
                    }
                }

                return $pointer . $key;
            },
            ''
        );

        if ($is_conditional) {
            $pointer = '?' . $pointer;
        }

        return $pointer;
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
     * @param array $expansion In case pointer needs expansion, this handles an flat array
     * with the expansion values.
     *
     * @return mixed Attribute value.
     */
    public function get($pointer, &$expansion = [])
    {
        $pointer = (string) $pointer;

        if (isset($this->data[$pointer])) {
            return $this->data[$pointer];
        }

        if (strstr($pointer, '[]') !== false) {
            return $this->get_expanded($pointer, $expansion);
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

        $expansion[] = $value;
        return $value;
    }

    /**
     * Gets values from an expanded finger pointer.
     *
     * @param string $pointer Finger pointer.
     * @param array $expansion Handle for the expansion's flat array of values.
     *
     * @return array Hierarchical structure of values result of the expansion.
     */
    private function get_expanded($pointer, &$expansion = [])
    {
        $flat = preg_match('/\[\]$/', $pointer);

        $parts = explode('[]', $pointer);
        $before = $parts[0];
        $after = implode('[]', array_filter(array_slice($parts, 1)));

        if (!$before) {
            if (!wp_is_numeric_array($this->data)) {
                return [];
            }

            $items = $this->data;
        } else {
            $items = $this->get($before);
        }

        if (empty($after) || !wp_is_numeric_array($items)) {
            return $items;
        }

        for ($i = 0; $i < count($items); $i++) {
            $pointer = "{$before}[$i]{$after}";
            $items[$i] = $this->get($pointer, $expansion);
        }

        if ($flat) {
            return $expansion;
        }

        return $items;
    }

    /**
     * Sets the attribute value on the data.
     *
     * @param string $pointer JSON finger pointer.
     * @param mixed $value Attribute value.
     * @param boolean $unset If true, unsets the attribute.
     *
     * @return array Updated data.
     */
    public function set($pointer, $value, $unset = false)
    {
        if ($this->$pointer) {
            $this->$pointer = $value;
            return $this->data;
        }

        if (strstr($pointer, '[]') !== false) {
            return $this->set_expanded($pointer, $value, $unset);
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
                if (intval($key) == $key) {
                    if (!wp_is_numeric_array($partial)) {
                        return $data;
                    }

                    $key = intval($key);
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
     * Sets values based on the expansion of the finger pointer.
     *
     * @param string $pointer Finger pointer.
     * @param array $values Array of values.
     * @param boolean $unset If true, unsets the attributes.
     *
     * @return array Updated data.
     */
    private function set_expanded($pointer, $values, $unset)
    {
        $parts = array_filter(explode('[]', $pointer));
        $before = $parts[0];
        $after = implode('[]', array_slice($parts, 1));

        if ($unset) {
            $values = $this->get($before);
        }

        if (!wp_is_numeric_array($values)) {
            return;
        }

        for ($i = count($values) - 1; $i >= 0; $i--) {
            $pointer = "{$before}[{$i}]{$after}";

            if ($unset) {
                $this->unset($pointer);
            } else {
                $this->set($pointer, $values[$i]);
            }
        }

        $values = $this->get($before);

        if (wp_is_numeric_array($values)) {
            ksort($values);
            $this->set($before, $values);
        }

        return $this->data;
    }

    /**
     * Unsets the attribute from the data.
     *
     * @param string $pointer JSON finger pointer.
     */
    public function unset($pointer)
    {
        if (isset($this->data[$pointer])) {
            if (intval($pointer) == $pointer) {
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
     * @param boolean &$is_conditional Reference to handle if the pointer is
     * conditional.
     *
     * @return boolean True if attribute is set.
     */
    public function isset($pointer, &$is_conditional = false)
    {
        $keys = self::parse($pointer, $is_conditional);

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

                if (strstr($pointer, '[]') === false) {
                    return isset($parent[$key]);
                }

                if (!wp_is_numeric_array($parent)) {
                    return false;
                }

                if ($key === INF) {
                    return true;
                }

                foreach ($parent as $item) {
                    if (isset($item[$key])) {
                        return true;
                    }
                }

                return false;
        }
    }
}
