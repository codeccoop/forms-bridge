<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_bridge_schema',
    function ($schema, $addon) {
        if ($addon !== 'odoo') {
            return $schema;
        }

        $schema['properties']['credential'] = [
            'type' => 'string',
            'description' => __(
                'Name of the database credential',
                'forms-bridge'
            ),
            'default' => '',
        ];

        $schema['required'][] = 'credential';

        $schema['properties']['endpoint']['name'] = __('Model', 'forms-bridge');
        $schema['properties']['endpoint']['description'] = __(
            'Name of the target DB model',
            'forms-bridge'
        );

        $schema['properties']['method']['description'] = __(
            'RPC call method name',
            'forms-bridge'
        );
        $schema['properties']['method']['enum'] = [
            'search',
            'search_read',
            'read',
            'write',
            'create',
            'unlink',
            'fields_get',
        ];

        return $schema;
    },
    10,
    2
);

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'odoo') {
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
                        'name' => 'database',
                        'label' => __('Database', 'forms-bridge'),
                        'description' => __(
                            'Name of the database',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'user',
                        'label' => __('User', 'forms-bridge'),
                        'description' => __(
                            'User name or email',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'password',
                        'description' => __(
                            'User password or API token',
                            'forms-bridge'
                        ),
                        'label' => __('Password', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'default' => 'Odoo',
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'endpoint',
                        'label' => __('Model', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'method',
                        'label' => __('Method', 'forms-bridge'),
                        'type' => 'string',
                        'value' => 'create',
                        'required' => true,
                    ],
                ],
                'bridge' => [
                    'name' => '',
                    'form_id' => '',
                    'backend' => '',
                    'credential' => '',
                    'endpoint' => '',
                ],
                'backend' => [
                    'name' => 'Odoo',
                    'headers' => [
                        [
                            'name' => 'Accept',
                            'value' => 'application/json',
                        ],
                    ],
                ],
                'credential' => [
                    'name' => '',
                    'database' => '',
                    'user' => '',
                    'password' => '',
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
        if (strpos($template_id, 'odoo-') !== 0) {
            return $data;
        }

        $custom_field_names = array_column(
            $data['bridge']['custom_fields'],
            'name'
        );

        $index = array_search('tag_ids', $custom_field_names);
        if ($index !== false) {
            $field = $data['bridge']['custom_fields'][$index];
            $tags = $field['value'] ?? [];

            for ($i = 0; $i < count($tags); $i++) {
                $data['bridge']['custom_fields'][] = [
                    'name' => "tag_ids[{$i}]",
                    'value' => $tags[$i],
                ];

                $data['bridge']['mutations'][0][] = [
                    'from' => "tag_ids[{$i}]",
                    'to' => "tag_ids[{$i}]",
                    'cast' => 'integer',
                ];
            }

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        $index = array_search('categ_ids', $custom_field_names);
        if ($index !== false) {
            $field = $data['bridge']['custom_fields'][$index];
            $tags = $field['value'] ?? [];

            for ($i = 0; $i < count($tags); $i++) {
                $data['bridge']['custom_fields'][] = [
                    'name' => "categ_ids[{$i}]",
                    'value' => $tags[$i],
                ];

                $data['bridge']['mutations'][0][] = [
                    'from' => "categ_ids[{$i}]",
                    'to' => "categ_ids[{$i}]",
                    'cast' => 'integer',
                ];
            }

            array_splice($data['bridge']['custom_fields'], $index, 1);
        }

        $index = array_search('list_ids', $custom_field_names);
        if ($index !== false) {
            $field = $data['bridge']['custom_fields'][$index];
            $lists = $field['value'] ?? [];

            for ($i = 0; $i < count($lists); $i++) {
                $data['bridge']['custom_fields'][] = [
                    'name' => "list_ids[{$i}]",
                    'value' => $lists[$i],
                ];

                $data['bridge']['mutations'][0][] = [
                    'from' => "list_ids[{$i}]",
                    'to' => "list_ids[{$i}]",
                    'cast' => 'integer',
                ];
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
        if ($addon !== 'odoo') {
            return $schema;
        }

        $schema = array_merge($schema, [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'odoo-credential',
            'type' => 'object',
            'description' => __(
                'Odoo database RPC login credentials',
                'forms-bridge'
            ),
        ]);

        $schema['properties']['database'] = [
            'type' => 'string',
            'minLength' => 1,
        ];

        $schema['properties']['user'] = [
            'type' => 'string',
            'minLength' => 1,
        ];

        $schema['properties']['password'] = [
            'type' => 'string',
            'minLength' => 1,
        ];

        $schema['required'][] = 'database';
        $schema['required'][] = 'user';
        $schema['required'][] = 'password';

        return $schema;
    },
    10,
    2
);
