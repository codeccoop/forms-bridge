<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'brevo') {
            return $defaults;
        }

        return wpct_plugin_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'description' => __(
                            'Label of the Brevo API backend connection',
                            'forms-bridge'
                        ),
                        'default' => 'Brevo API',
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'value' => 'https://api.brevo.com',
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'api-key',
                        'label' => __('API Key', 'forms-bridge'),
                        'description' => __(
                            'Get it from your <a href="https://app.brevo.com/settings/keys/api" target="_blank">account</a>',
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
                    'base_url' => 'https://api.brevo.com',
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
        if (strpos($template_id, 'brevo-') !== 0) {
            return $data;
        }

        $custom_field_names = array_column(
            $data['bridge']['custom_fields'],
            'name'
        );

        $index = array_search('listIds', $custom_field_names);

        if ($index !== false) {
            $field = $data['bridge']['custom_fields'][$index];

            for ($i = 0; $i < count($field['value']); $i++) {
                $data['bridge']['custom_fields'][] = [
                    'name' => "listIds[{$i}]",
                    'value' => $field['value'][$i],
                ];

                $data['bridge']['mutations'][0][] = [
                    'from' => "listIds[{$i}]",
                    'to' => "listIds[{$i}]",
                    'cast' => 'integer',
                ];
            }

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        $index = array_search('includeListIds', $custom_field_names);

        if ($index !== false) {
            $field = $data['bridge']['custom_fields'][$index];

            for ($i = 0; $i < count($field['value']); $i++) {
                $data['bridge']['custom_fields'][] = [
                    'name' => "includeListIds[{$i}]",
                    'value' => $field['value'][$i],
                ];

                $data['bridge']['mutations'][0][] = [
                    'from' => "includeListIds[{$i}]",
                    'to' => "includeListIds[{$i}]",
                    'cast' => 'integer',
                ];
            }

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        $index = array_search('redirectionUrl', $custom_field_names);

        if ($index !== false) {
            $field = &$data['bridge']['custom_fields'][$index];

            $field['value'] = (string) filter_var(
                (string) $field['value'],
                FILTER_SANITIZE_URL
            );

            $parsed = parse_url($field['value']);

            if (!isset($parsed['host'])) {
                $site_url = get_site_url();

                $field['value'] =
                    $site_url .
                    '/' .
                    preg_replace('/^\/+/', '', $field['value']);
            }
        }

        return $data;
    },
    10,
    2
);
