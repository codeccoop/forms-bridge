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
                            'Label of the MailChimp API backend connection',
                            'forms-bridge'
                        ),
                        'default' => 'MailChimp API',
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'value' => 'https://{dc}.api.mailchimp.com',
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'datacenter',
                        'label' => __('Datacenter', 'forms-bridge'),
                        'description' => __(
                            'First part of the URL of your mailchimp account or last part of your API key',
                            'forms-bridge'
                        ),
                        'required' => true,
                        'type' => 'options',
                        'options' => [
                            [
                                'label' => 'us1',
                                'value' => 'us1',
                            ],
                            [
                                'label' => 'us2',
                                'value' => 'us2',
                            ],
                            [
                                'label' => 'us3',
                                'value' => 'us3',
                            ],
                            [
                                'label' => 'us4',
                                'value' => 'us4',
                            ],
                            [
                                'label' => 'us5',
                                'value' => 'us5',
                            ],
                            [
                                'label' => 'us6',
                                'value' => 'us6',
                            ],
                            [
                                'label' => 'us7',
                                'value' => 'us7',
                            ],
                            [
                                'label' => 'us8',
                                'value' => 'us8',
                            ],
                            [
                                'label' => 'us9',
                                'value' => 'us9',
                            ],
                            [
                                'label' => 'us10',
                                'value' => 'us10',
                            ],
                            [
                                'label' => 'us11',
                                'value' => 'us11',
                            ],
                            [
                                'label' => 'us12',
                                'value' => 'us12',
                            ],
                            [
                                'label' => 'us13',
                                'value' => 'us13',
                            ],
                        ],
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'api-key',
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
                ],
                'bridge' => [
                    'backend' => '',
                    'endpoint' => '',
                    'method' => 'POST',
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

        $header_names = array_column($data['backend']['header'], 'name');
        $custom_field_names = array_column(
            $data['bridge']['custom_fields'],
            'name'
        );

        $index = array_search('datacenter', $header_names);
        if ($index !== false) {
            $dc = $data['backend']['headers'][$index]['value'];
            $data['backend']['base_url'] = preg_replace(
                '/\{dc\}/',
                $dc,
                $data['backend']['base_url']
            );

            array_splice($data['backend']['headers'], $index, 1);
        }

        $index = array_search('api-key', $header_names);
        if ($index !== false) {
            $key = $data['backend']['headers'][$index];

            $data['backend']['headers'][] = [
                'name' => 'Authorization',
                'value' => 'Basic ' . base64_encode("forms-bridge:{$key}"),
            ];

            array_splice($data['backend']['headers'], $index, 1);
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
