<?php

namespace FORMS_BRIDGE;

use ValueError;
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
     * Parse a json finger and returns it as an array of keys.
     *
     * @param string $finger JSON finger.
     *
     * @return array Array with finger keys.
     */
    public static function parse($finger)
    {
        $len = strlen($finger);
        $keys = [];
        $key = '';
        $closured = false;
        $index = 0;

        // Parse finger as a charstring iteration
        for ($i = 0; $i < $len; $i++) {
            $char = $finger[$i];
            if ($closured) {
                if ($char === '"') {
                    $closured = false;
                } else {
                    $key .= $char;
                }
            } else {
                if ($char === '"') {
                    $closured = true;
                } elseif ($char === '.') {
                    $keys[] = $key;
                    $key = '';
                } elseif ($char === '[') {
                    $keys[] = $key;
                    $key = '';

                    $i = $from = $i + 1;
                    $index = '';
                    while ($finger[$i] !== ']' && $i < $len) {
                        $index .= $finger[$i];
                        $i += 1;
                    }

                    if (!((int) $index == $index)) {
                        throw new ValueError('Invalid array index at ' . $from);
                    }

                    $index = (int) $index;
                    $keys[] = $index;
                    $i += 1;
                    if (strlen($finger) > $i) {
                        if ($finger[$i] !== '.') {
                            throw new ValueError(
                                'Invalid finger syntax at ' . $i
                            );
                        }
                    }
                } else {
                    $key .= $char;
                }
            }
        }

        if ($key) {
            $keys[] = $key;
        }

        return $keys;
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
     * @param string $finger JSON finger.
     *
     * @return mixed Attribute value.
     */
    public function get($finger)
    {
        if ($this->$finger) {
            return $this->$finger;
        }

        $value = null;
        try {
            $keys = static::parse($finger);

            $value = $this->data;
            foreach ($keys as $key) {
                if (!isset($value[$key])) {
                    $value = null;
                    break;
                }
                $value = $value[$key];
            }
        } catch (Error $e) {
            error_log($e->getMessage());
            return null;
        }

        return $value;
    }

    /**
     * Sets the attribute value on the data.
     *
     * @param string $finger JSON finger.
     * @param mixed $value Attribute value.
     * @param boolean $unset If true, unsets the attribute.
     *
     * @return array Data after the attribute update.
     */
    public function set($finger, $value, $unset = false)
    {
        if ($this->$finger) {
            $this->$finger = $value;
        }

        $data = $this->data;
        $breadcrumb = [];
        try {
            $keys = static::parse($finger);
            $partial = &$data;
            for ($i = 0; $i < count($keys) - 1; $i++) {
                if (!is_array($partial)) {
                    throw new TypeError(
                        'Finger points to a non traversable value'
                    );
                }

                $key = $keys[$i];
                if (!isset($partial[$key])) {
                    $partial[$key] = [];
                }
                $breadcrumb[] = ['partial' => &$partial, 'key' => $key];
                $partial = &$partial[$key];
            }

            $key = $keys[$i];
            if ($unset) {
                unset($partial[$key]);
                for ($i = count($breadcrumb) - 1; $i >= 0; $i--) {
                    $step = $breadcrumb[$i];
                    $partial = &$step['partial'];
                    $key = $step['key'];
                    if (!empty($partial[$key])) {
                        break;
                    }
                    unset($partial[$key]);
                }
            } else {
                $partial[$key] = $value;
            }
        } catch (Error $e) {
            error_log($e->getMessage());
            return $this->data;
        }

        $this->data = $data;
        return $data;
    }

    /**
     * Unsets the attribute from the data.
     *
     * @param string $finger JSON finger.
     */
    public function unset($finger)
    {
        return $this->set($finger, null, true);
    }

    /**
     * Checks if the json finger is set on the data.
     *
     * @param string $finger JSON finger.
     *
     * @return boolean True if attribute is set.
     */
    public function isset($finger)
    {
        return !empty($this->get($finger));
    }
}
