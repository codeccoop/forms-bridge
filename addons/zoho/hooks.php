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
                        'name' => 'organization_id',
                        'label' => __('Organization ID', 'forms-bridge'),
                        'type' => 'string',
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
                ],
                'bridge' => [
                    'backend' => 'Zoho API',
                    'endpoint' => '',
                    'credential' => '',
                ],
                'credential' => [
                    'name' => '',
                    'organization_id' => '',
                    'client_id' => '',
                    'client_secret' => '',
                    'scope' =>
                        'ZohoCRM.modules.ALL,ZohoCRM.settings.layouts.READ,ZohoCRM.users.READ',
                ],
                'backend' => [
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
                    'minLength' => 10,
                ],
                'access_token' => [
                    'type' => 'string',
                    'show_in_rest' => false,
                ],
                'refresh_token' => [
                    'type' => 'string',
                    'show_in_rest' => false,
                ],
                'expires_at' => [
                    'type' => 'integer',
                    'show_in_rest' => false,
                ],
                'enabled' => [
                    'type' => 'boolean',
                    'default' => false,
                ],
            ],
            'required' => [
                'name',
                'type',
                'client_id',
                'client_secret',
                'scope',
            ],
            'additionalProperties' => false,
        ];
    },
    10,
    2
);
