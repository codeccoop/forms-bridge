<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_bridge_schema',
    function ($schema, $addon) {
        if ($addon !== 'zoho') {
            return $schema;
        }

        $schema['properties']['credential'] = [
            'type' => 'string',
            'description' => __('Name of the OAuth credential', 'forms-bridge'),
            'default' => '',
        ];

        return $schema;
    },
    10,
    2
);

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
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'type',
                        'label' => __('Auhentication type', 'forms-bridge'),
                        'type' => 'options',
                        'options' => [
                            [
                                'value' => 'Server-based',
                                'label' => 'Server-based',
                            ],
                            [
                                'value' => 'Self Client',
                                'label' => 'Self Client',
                            ],
                        ],
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'region',
                        'label' => __('Datacenter', 'forms-bridge'),
                        'type' => 'options',
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
                        ],
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'client_id',
                        'label' => __('Client ID', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'client_secret',
                        'label' => __('Client secret', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'organization_id',
                        'label' => __('Organization ID', 'forms-bridge'),
                        'description' => __(
                            'Required if you want to use Self Client authentication protocol',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'scope',
                        'label' => __('Scope', 'forms-bridge'),
                        'description' => __(
                            'See <a href="https://www.zoho.com/accounts/protocol/oauth/scope.html">the documentation</a> for more information',
                            'forms-bridge'
                        ),
                        'type' => 'string',
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
                        'type' => 'options',
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
                    'credential' => '',
                ],
                'credential' => [
                    'name' => '',
                    'type' => '',
                    'client_id' => '',
                    'client_secret' => '',
                    'organization_id' => '',
                    'scope' =>
                        'ZohoCRM.modules.ALL,ZohoCRM.settings.layouts.READ,ZohoCRM.users.READ',
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

        return $data;
    },
    10,
    2
);

add_filter(
    'forms_bridge_credential_schema',
    function ($schema, $addon) {
        if ($addon !== 'zoho') {
            return $schema;
        }

        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'zoho-credential',
            'type' => 'object',
            'description' => __('Zoho OAuth API credential', 'forms-bridge'),
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'minLength' => 1,
                ],
                'type' => [
                    'type' => 'string',
                    'enum' => ['Server-based', 'Self Client'],
                    'default' => 'Server-based',
                ],
                'region' => [
                    'type' => 'string',
                    'enum' => [
                        'zoho.com',
                        'zoho.eu',
                        'zoho.in',
                        'zoho.com.cn',
                        'zoho.com.au',
                        'zoho.jp',
                    ],
                    'default' => 'zoho.com',
                ],
                'client_id' => [
                    'type' => 'string',
                    'minLength' => 1,
                ],
                'client_secret' => [
                    'type' => 'string',
                    'minLength' => 1,
                ],
                'scope' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'default' =>
                        'ZohoCRM.modules.ALL,ZohoCRM.settings.layouts.READ,ZohoCRM.users.READ',
                ],
                'organization_id' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'access_token' => [
                    'type' => 'string',
                    'default' => '',
                    'public' => false,
                ],
                'refresh_token' => [
                    'type' => 'string',
                    'default' => '',
                    'public' => false,
                ],
                'expires_at' => [
                    'type' => 'integer',
                    'default' => 0,
                    'public' => false,
                ],
                'enabled' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'is_valid' => [
                    'type' => 'boolean',
                    'default' => false,
                ],
            ],
            'required' => [
                'name',
                'type',
                'region',
                'client_id',
                'client_secret',
                'scope',
                'access_token',
                'refresh_token',
                'expires_at',
            ],
            'additionalProperties' => false,
        ];
    },
    10,
    2
);
