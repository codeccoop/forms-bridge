<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

trait Form_Bridge_Mutations
{
    /**
     * Apply cast mappers to data.
     *
     * @param array $data Array of data.
     *
     * @return array Data modified by the bridge's mappers.
     */
    final public function apply_mutation($data, $mutation = null)
    {
        if (!is_array($data)) {
            return $data;
        }

        $finger = new JSON_Finger($data);

        if ($mutation === null) {
            $mutation = $this->mutations[0] ?? [];
        }

        foreach ($mutation as $mapper) {
            $is_valid =
                JSON_Finger::validate($mapper['from']) &&
                JSON_Finger::validate($mapper['to']);

            if (!$is_valid) {
                continue;
            }

            $isset = $finger->isset($mapper['from'], $is_conditional);
            if (!$isset) {
                if ($is_conditional) {
                    continue;
                }

                $value = null;
            } else {
                $value = $finger->get($mapper['from']);
            }

            if (
                ($mapper['cast'] !== 'copy' &&
                    $mapper['from'] !== $mapper['to']) ||
                $mapper['cast'] === 'null'
            ) {
                $finger->unset($mapper['from']);
            }

            if ($mapper['cast'] !== 'null') {
                $finger->set(
                    $mapper['to'],
                    $this->cast($value, $mapper['cast'], $mapper['from'])
                );
            }
        }

        return $finger->data();
    }

    /**
     * Casts value to the given type.
     *
     * @param mixed $value Original value.
     * @param string $type Target type to cast value.
     *
     * @return mixed
     */
    private function cast($value, $cast, $pointer = null)
    {
        if ($pointer && strstr($pointer, '[]') !== false) {
            return $this->cast_expanded($value, $cast, $pointer);
        }

        switch ($cast) {
            case 'string':
                return (string) $value;
            case 'integer':
                return (int) $value;
            case 'number':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'json':
                return wp_json_encode($value, JSON_UNESCAPED_UNICODE);
            case 'csv':
                return implode(',', (array) $value);
            case 'concat':
                return implode(' ', (array) $value);
            case 'join':
                return implode('', (array) $value);
            case 'inherit':
                return $value;
            case 'copy':
                return $value;
            case 'null':
                return;
            default:
                return (string) $value;
        }
    }

    private function cast_expanded($values, $cast, $pointer)
    {
        $expanded = preg_match('/\[\]$/', $pointer);

        $parts = array_filter(explode('[]', $pointer));
        $before = $parts[0];
        $after = implode('[]', array_slice($parts, 1));

        if (empty($after)) {
            if ($expanded && wp_is_numeric_array($values)) {
                return array_map(function ($value) use ($cast, $before) {
                    return $this->cast($value, $cast, $before);
                }, $values);
            }

            return $this->cast($values, $cast, $before);
        }

        for ($i = 0; $i < count($values); $i++) {
            $pointer = "{$before}[{$i}]{$after}";
            $values[$i] = $this->cast($values[$i], $cast, $pointer);
        }

        return $values;
    }
}
