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

            $unset = $mapper['cast'] === 'null';

            if ($mapper['cast'] !== 'copy') {
                $unset =
                    $unset ||
                    preg_replace('/^\?/', '', $mapper['from']) !==
                        $mapper['to'];
            }

            if ($unset) {
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
                if (!is_array($value)) {
                    return '';
                }

                return wp_json_encode($value, JSON_UNESCAPED_UNICODE);
            case 'csv':
                if (!wp_is_numeric_array($value)) {
                    return '';
                }

                return implode(',', (array) $value);
            case 'concat':
                if (!wp_is_numeric_array($value)) {
                    return '';
                }

                return implode(' ', (array) $value);
            case 'join':
                if (!wp_is_numeric_array($value)) {
                    return '';
                }

                return implode('', (array) $value);
            case 'sum':
                if (!wp_is_numeric_array($value)) {
                    return 0;
                }

                return array_reduce(
                    (array) $value,
                    static function ($total, $val) {
                        return $total + $val;
                    },
                    0
                );
            case 'count':
                if (!is_array($value)) {
                    return 0;
                }

                return count((array) $value);
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
                return $this->cast($value, [
                    'from' => '',
                    'to' => '',
                    'cast' => $mapper['cast'],
                ]);
            }, $values);
        }

        preg_match_all('/\[\](?=[^\[])/', $mapper['to'], $to_expansions);
        preg_match_all('/\[\](?=[^\[])/', $mapper['from'], $from_expansions);

        if (count($to_expansions[0]) > count($from_expansions)) {
            return [];
        }

        $parts = array_filter(explode('[]', $mapper['from']));
        $before = $parts[0];
        $after = implode('[]', array_slice($parts, 1));

        for ($i = 0; $i < count($values); $i++) {
            $pointer = "{$before}[{$i}]{$after}";
            $values[$i] = $this->cast($values[$i], [
                'from' => $pointer,
                'to' => '',
                'cast' => $mapper['cast'],
            ]);
        }

        return $values;
    }

    final public function setup_conditional_mappers($form)
    {
        foreach ($form['fields'] as $field) {
            $is_conditional = $field['conditional'] ?? false;

            if (
                $field['schema']['type'] === 'array' &&
                ($field['schema']['additionalItems'] ?? true) === false
            ) {
                $min_items = $field['schema']['minItems'] ?? 0;
                $max_items = $field['schema']['maxItems'] ?? 0;

                $is_conditional = $is_conditional || $min_items < $max_items;
            }

            if ($is_conditional) {
                $to = $field['name'];

                for ($i = 0; $i < count($this->data['mutations']); $i++) {
                    $mutation = $this->data['mutations'][$i];

                    for ($j = 0; $j < count($mutation); $j++) {
                        $mapper = $this->data['mutations'][$i][$j];

                        $from = preg_replace('/\[\d*\]/', '', $mapper['from']);
                        if ($from !== $to) {
                            continue;
                        }

                        $this->data['mutations'][$i][$j]['from'] =
                            '?' . $mapper['from'];
                        $to = preg_replace('/\[\d*\]/', '', $mapper['to']);
                    }
                }
            }
        }
    }
}
