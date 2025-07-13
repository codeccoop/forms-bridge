<?php

use FORMS_BRIDGE\Google_Sheets_Addon;

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_bridge_schema',
    function ($schema, $addon) {
        if ($addon !== 'gsheets') {
            return $schema;
        }

        return wpct_plugin_merge_object(
            [
                'properties' => [
                    'method' => [
                        'enum' => ['GET'],
                        'value' => 'GET',
                    ],
                    'spreadsheet' => [
                        '$ref' => '#/gsheets/spreadsheets/id',
                        'description' => __(
                            'ID of the spreadhseet',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                    ],
                    'tab' => [
                        'description' => __(
                            'Name of the spreadsheet tab',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'endpoint' => [
                        'description' => __(
                            'Concatenation of the spreadsheet ID and the tab name by double colons',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'pattern' => '^.*::.*$',
                    ],
                ],
                'required' => ['spreadsheet', 'tab'],
            ],
            $schema
        );
    },
    10,
    2
);

add_filter(
    'forms_bridge_template_schema',
    function ($schema, $addon) {
        if ($addon !== 'gsheets') {
            return $schema;
        }

        $schema['properties']['bridge']['properties']['spreadsheet'] = [
            'type' => 'string',
        ];

        $schema['properties']['bridge']['required'][] = 'spreadsheet';

        $schema['properties']['bridge']['properties']['tab'] = [
            'type' => 'string',
        ];

        $schema['properties']['bridge']['required'][] = 'tab';

        $schema['properties']['bridge']['properties']['endpoint'] = [
            'type' => 'string',
        ];

        $schema['properties']['bridge']['required'][] = 'endpoint';

        $schema['properties']['spreadsheet'] = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string'],
                'tab' => ['type' => 'string'],
            ],
            'required' => ['id', 'tab'],
            'additionalProperties' => false,
        ];

        return $schema;
    },
    10,
    2
);

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'gsheets') {
            return $defaults;
        }

        return wpct_plugin_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#spreadsheet',
                        'name' => 'id',
                        'label' => 'Spreadsheet',
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#spreadsheet',
                        'name' => 'tab',
                        'label' => 'Tab',
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'value' => Google_Sheets_Addon::static_backend['name'],
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'value' =>
                            Google_Sheets_Addon::static_backend['base_url'],
                    ],
                ],
                'backend' => Google_Sheets_Addon::static_backend,
                'bridge' => [
                    'backend' => Google_Sheets_Addon::static_backend['name'],
                    'endpoint' => '',
                    'spreadsheet' => '',
                    'tab' => '',
                ],
                'spreadsheet' => [
                    'id' => '',
                    'tab' => '',
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
        if (strpos($template_id, 'gsheets-') !== 0) {
            return $data;
        }

        $data['bridge']['spreadsheet'] = $data['spreadsheet']['id'];
        $data['bridge']['tab'] = $data['spreadsheet']['tab'];
        $data['bridge']['endpoint'] =
            $data['spreadsheet']['id'] . '::' . $data['spreadsheet']['tab'];

        return $data;
    },
    10,
    2
);
