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
                        'ref' => '#credential',
                        'name' => 'name',
                        'label' => __('Name', 'forms-bridge'),
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'schema',
                        'type' => 'text',
                        'value' => 'Basic',
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'client_id',
                        'type' => 'text',
                        'value' => 'forms-bridge',
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'client_secret',
                        'label' => __('API key', 'forms-bridge'),
                        'description' => __(
                            'Get it from your <a href="https://us1.admin.mailchimp.com/account/api/" target="_blank">account</a>',
                            'forms-bridge'
                        ),
                        'type' => 'text',
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
                        'type' => 'select',
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
                ],
                'credential' => [
                    'name' => '',
                    'schema' => 'Basic',
                    'client_id' => '',
                    'client_secret' => '',
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

        $index = array_search(
            'skip_merge_validation',
            array_column($data['bridge']['custom_fields'], 'name')
        );

        if ($index !== false) {
            $endpoint = $data['bridge']['endpoint'];
            $parsed = wp_parse_url($endpoint);

            $path = $parsed['path'] ?? '';

            $query = [];
            wp_parse_str($parsed['query'] ?? '', $query);
            $query['skip_merge_validation'] = 'true';
            $querystr = http_build_query($query);

            $data['bridge']['endpoint'] = $path . '?' . $querystr;

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        $index = array_search(
            'list_id',
            array_column($data['bridge']['custom_fields'], 'name')
        );

        if ($index !== false) {
            $list_id = $data['bridge']['custom_fields'][$index]['value'];
            $data['bridge']['endpoint'] = preg_replace(
                '/\{list_id\}/',
                $list_id,
                $data['bridge']['endpoint']
            );

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        $index = array_search(
            'tags',
            array_column($data['bridge']['custom_fields'], 'name')
        );

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
