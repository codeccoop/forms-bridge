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
                $finger->set($mapper['to'], $this->cast($value, $mapper));
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
    private function cast($value, $mapper)
    {
        if (strstr($mapper['from'], '[]') !== false) {
            return $this->cast_expanded($value, $mapper);
        }

        if (preg_match('/\[\]$/', $mapper['to'])) {
            if (!wp_is_numeric_array($value)) {
                return [];
            }

            $item_mapper = $mapper;
            $item_mapper['to'] = substr($item_mapper['to'], 0, -2);

            return array_map(function ($item) use ($item_mapper) {
                return $this->cast($item, $item_mapper);
            }, $value);
        }

        switch ($mapper['cast']) {
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

    private function cast_expanded($values, $mapper)
    {
        if (!wp_is_numeric_array($values)) {
            return [];
        }

        $is_expanded = preg_match('/\[\]$/', $mapper['from']);

        if ($is_expanded) {
            return array_map(function ($value) use ($mapper) {
                $item_mapper = $mapper;
                $item_mapper['from'] = substr($item_mapper['from'], 0, -2);
                return $this->cast($value, $item_mapper);
            }, $values);
        }

        $parts = array_filter(explode('[]', $mapper['to']));
        $before = $parts[0];
        $after = implode('[]', array_slice($parts, 1));

        for ($i = 0; $i < count($values); $i++) {
            $pointer = "{$before}[{$i}]{$after}";
            $item_mapper = $mapper;
            $item_mapper['from'] = $pointer;
            $values[$i] = $this->cast($values[$i], $item_mapper);
        }

        return $values;
    }
}
