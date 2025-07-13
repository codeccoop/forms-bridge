<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'holded') {
            return $defaults;
        }

        $defaults = apply_filters(
            'forms_bridge_template_defaults',
            $defaults,
            'rest',
            $schema
        );

        return wpct_plugin_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'description' => __(
                            'Label of the Holded API backend connection',
                            'forms-bridge'
                        ),
                        'default' => 'Holded API',
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'value' => 'https://api.holded.com',
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'key',
                        'label' => __('API Key', 'forms-bridge'),
                        'description' => __(
                            'Get it from your <a href="https://app.holded.com/api" target="_blank">account</a>',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'method',
                        'value' => 'POST',
                    ],
                ],
                'bridge' => [
                    'method' => 'POST',
                ],
                'backend' => [
                    'base_url' => 'https://api.holded.com',
                ],
            ],
            $defaults,
            $schema
        );
    },
    10,
    3
);

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_id) {
        if (strpos($template_id, 'holded-') !== 0) {
            return $data;
        }

        $index = array_search(
            'tags',
            array_column($data['bridge']['custom_fields'], 'name')
        );

        if ($index !== false) {
            $field = &$data['bridge']['custom_fields'][$index];

            if (!empty($field['value'])) {
                $tags = array_filter(
                    array_map('trim', explode(',', strval($field['value'])))
                );

                for ($i = 0; $i < count($tags); $i++) {
                    $data['bridge']['custom_fields'][] = [
                        'name' => "tags[{$i}]",
                        'value' => $tags[$i],
                    ];
                }
            }

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        return $data;
    },
    10,
    2
);
