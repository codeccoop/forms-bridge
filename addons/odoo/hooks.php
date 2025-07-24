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

        $schema['properties']['endpoint']['title'] = __(
            'Model',
            'forms-bridge'
        );
        $schema['properties']['endpoint']['description'] = __(
            'Name of the target DB model',
            'forms-bridge'
        );
        $schema['properties']['endpoint']['default'] = 'res.partner';

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
        $schema['properties']['method']['default'] = 'create';

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
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'schema',
                        'type' => 'text',
                        'value' => 'RPC',
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'database',
                        'label' => __('Database', 'forms-bridge'),
                        'description' => __(
                            'Name of the database',
                            'forms-bridge'
                        ),
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'client_id',
                        'label' => __('User', 'forms-bridge'),
                        'description' => __(
                            'User name or email',
                            'forms-bridge'
                        ),
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'client_secret',
                        'description' => __(
                            'User password or API token',
                            'forms-bridge'
                        ),
                        'label' => __('Password', 'forms-bridge'),
                        'type' => 'text',
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
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'method',
                        'label' => __('Method', 'forms-bridge'),
                        'type' => 'text',
                        'value' => 'create',
                        'required' => true,
                    ],
                ],
                'bridge' => [
                    'name' => '',
                    'form_id' => '',
                    'backend' => '',
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
                    'schema' => 'RPC',
                    'client_id' => '',
                    'client_secret' => '',
                    'database' => '',
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

        $index = array_search(
            'tag_ids',
            array_column($data['bridge']['custom_fields'], 'name')
        );

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

        $index = array_search(
            'categ_ids',
            array_column($data['bridge']['custom_fields'], 'name')
        );

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

        $index = array_search(
            'list_ids',
            array_column($data['bridge']['custom_fields'], 'name')
        );

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

        $index = array_search(
            'allday',
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
