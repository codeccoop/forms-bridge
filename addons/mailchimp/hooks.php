<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'mailchimp') {
            return $defaults;
        }

        return wpct_plugin_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'description' => __(
                            'Label of the MailChimp API backend connection',
                            'forms-bridge'
                        ),
                        'default' => 'MailChimp API',
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'description' => __(
                            'If needed, replace the datacenter param from the URL to match your account servers.',
                            'forms-bridge'
                        ),
                        'default' => 'https://{dc}.api.mailchimp.com',
                    ],
                    [
                        'ref' => '#backend/authentication',
                        'name' => 'client_id',
                        'label' => __('Client ID', 'forms-bridge'),
                        'type' => 'string',
                        'value' => 'forms-bridge',
                    ],
                    [
                        'ref' => '#backend/authentication',
                        'name' => 'client_secret',
                        'label' => __('API key', 'forms-bridge'),
                        'description' => __(
                            'Get it from your <a href="https://us1.admin.mailchimp.com/account/api/" target="_blank">account</a>',
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
                    [
                        'ref' => '#bridge/custom_fields[]',
                        'name' => 'list_id',
                        'label' => __('Audience', 'forms-bridge'),
                        'type' => 'options',
                        'options' => [
                            'endpoint' => '/3.0/lists',
                            'finger' => [
                                'value' => 'lists[].id',
                                'label' => 'lists[].name',
                            ],
                        ],
                        'required' => true,
                    ],
                    [
                        'ref' => '#bridge/custom_fields[]',
                        'name' => 'skip_merge_validation',
                        'label' => __(
                            'Skip merge fields validation',
                            'forms-bridge'
                        ),
                        'type' => 'boolean',
                        'default' => false,
                    ],
                ],
                'bridge' => [
                    'backend' => '',
                    'endpoint' => '',
                    'method' => 'POST',
                ],
                'backend' => [
                    'name' => 'Mailchimp API',
                    'base_url' => 'https://{dc}.api.mailchimp.com',
                    'authentication' => [
                        'type' => 'Basic',
                        'client_id' => 'forms-bridge',
                    ],
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
        if (strpos($template_id, 'mailchimp-') !== 0) {
            return $data;
        }

        $custom_field_names = array_column(
            $data['bridge']['custom_fields'],
            'name'
        );

        $index = array_search('skip_merge_validation', $custom_field_names);
        if ($index !== false) {
            if (!empty($data['bridge']['custom_field'][$index])) {
                $endpoint = $data['bridge']['endpoint'];
                $parsed = wp_parse_url($endpoint);

                $path = $parsed['path'] ?? '';

                $query = [];
                wp_parse_str($parsed['query'] ?? '', $query);
                $query['skip_merge_validation'] = 'true';
                $querystr = http_build_query($query);

                $data['bridge']['endpoint'] = $path . '?' . $querystr;
            }

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        $index = array_search('list_id', $custom_field_names);
        if ($index !== false) {
            $list_id = $data['bridge']['custom_fields'][$index]['value'];
            $data['bridge']['endpoint'] = preg_replace(
                '/\{list_id\}/',
                $list_id,
                $data['bridge']['endpoint']
            );

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        $index = array_search('tags', $custom_field_names);
        if ($index !== false) {
            $field = &$data['bridge']['custom_fields'][$index];

            $tags = array_filter(
                array_map('trim', explode(',', strval($field['value'])))
            );
            for ($i = 0; $i < count($tags); $i++) {
                $data['bridge']['custom_fields'][] = [
                    'name' => "tags[{$i}]",
                    'value' => $tags[$i],
                ];
            }

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        return $data;
    },
    10,
    2
);
