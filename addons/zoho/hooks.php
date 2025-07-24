<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'zoho') {
            return $defaults;
        }

        return wpct_plugin_merge_object(
            [
                'fields' => [
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
                        'value' => 'Bearer',
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'oauth_url',
                        'label' => __('Authorization URL', 'forms-bridge'),
                        'type' => 'text',
                        'value' => 'https://accounts.{region}/oauth/v2',
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'region',
                        'label' => __('Datacenter', 'forms-bridge'),
                        'type' => 'select',
                        'options' => [
                            [
                                'value' => 'zoho.com',
                                'label' => 'zoho.com',
                            ],
                            [
                                'value' => 'zoho.eu',
                                'label' => 'zoho.eu',
                            ],
                            [
                                'value' => 'zoho.in',
                                'label' => 'zoho.in',
                            ],
                            [
                                'value' => 'zoho.com.cn',
                                'label' => 'zoho.com.cn',
                            ],
                            [
                                'value' => 'zoho.com.au',
                                'label' => 'zoho.com.au',
                            ],
                            [
                                'value' => 'zoho.jp',
                                'label' => 'zoho.jp',
                            ],
                            [
                                'label' => 'zoho.sa',
                                'value' => 'zoho.sa',
                            ],
                        ],
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'client_id',
                        'label' => __('Client ID', 'forms-bridge'),
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'client_secret',
                        'label' => __('Client secret', 'forms-bridge'),
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'scope',
                        'label' => __('Scope', 'forms-bridge'),
                        // 'description' => __(
                        //     'See <a href="https://www.zoho.com/accounts/protocol/oauth/scope.html">the documentation</a> for more information',
                        //     'forms-bridge'
                        // ),
                        'type' => 'text',
                        'value' =>
                            'ZohoCRM.modules.ALL,ZohoCRM.settings.layouts.READ,ZohoCRM.users.READ',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'default' => 'Zoho API',
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'type' => 'select',
                        'options' => [
                            [
                                'label' => 'www.zohoapis.com',
                                'value' => 'https://www.zohoapis.com',
                            ],
                            [
                                'label' => 'www.zohoapis.eu',
                                'value' => 'https://www.zohoapis.eu',
                            ],
                            [
                                'label' => 'www.zohoapis.com.au',
                                'value' => 'https://www.zohoapis.com.au',
                            ],
                            [
                                'label' => 'www.zohoapis.in',
                                'value' => 'https://www.zohoapis.in',
                            ],
                            [
                                'label' => 'www.zohoapis.cn',
                                'value' => 'https://www.zohoapis.cn',
                            ],
                            [
                                'label' => 'www.zohoapis.jp',
                                'value' => 'https://www.zohoapis.jp',
                            ],
                            [
                                'label' => 'www.zohoapis.sa',
                                'value' => 'https://www.zohoapis.sa',
                            ],
                            [
                                'label' => 'www.zohoapis.ca',
                                'value' => 'https://www.zohoapis.ca',
                            ],
                        ],
                        'default' => 'https://www.zohoapis.com',
                        'required' => true,
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'method',
                        'value' => 'POST',
                    ],
                ],
                'bridge' => [
                    'backend' => 'Zoho API',
                    'endpoint' => '',
                ],
                'credential' => [
                    'name' => '',
                    'schema' => 'Bearer',
                    'oauth_url' => 'https://accounts.{region}/oauth/v2',
                    'scope' =>
                        'ZohoCRM.modules.ALL,ZohoCRM.settings.layouts.READ,ZohoCRM.users.READ',
                    'client_id' => '',
                    'client_secret' => '',
                    'access_token' => '',
                    'expires_at' => 0,
                    'refresh_token' => '',
                ],
                'backend' => [
                    'base_url' => 'https://www.zohoapis.{region}',
                    'headers' => [
                        [
                            'name' => 'Accept',
                            'value' => 'application/json',
                        ],
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
        if (strpos($template_id, 'zoho-') !== 0) {
            return $data;
        }

        $region = $data['credential']['region'];
        $data['credential']['oauth_url'] = preg_replace(
            '/{region}/',
            $region,
            $data['credential']['oauth_url']
        );
        unset($data['credential']['region']);

        $index = array_search(
            'Tag',
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
                        'name' => "Tag[{$i}].name",
                        'value' => $tags[$i],
                    ];
                }
            }

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        $index = array_search(
            'All_day',
            array_column($data['bridge']['custom_fields'], 'name')
        );

        if ($index !== false) {
            $data['form']['fields'] = array_filter(
                $data['form']['fields'],
                function ($field) {
                    return !in_array(
                        $field['name'],
                        [
                            'hour',
                            'minute',
                            __('Hour', 'forms-bridge'),
                            __('Minute', 'forms-bridge'),
                        ],
                        true
                    );
                }
            );

            $index = array_search(
                'duration',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                array_splice($data['bridge']['custom_fields'], $index, 1);
            }
        }

        return $data;
    },
    10,
    2
);
