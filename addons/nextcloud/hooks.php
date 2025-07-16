<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_bridge_schema',
    function ($schema, $addon) {
        if ($addon !== 'nextcloud') {
            return $schema;
        }

        $schema['properties']['endpoint']['name'] = __(
            'Filepath',
            'forms-bridge'
        );
        $schema['properties']['endpoint']['description'] = __(
            'Path to the CSV file from the root of your nextcloud file system directory',
            'forms-bridge'
        );
        $schema['properties']['endpoint']['pattern'] = '.+\.csv$';

        $schema['properties']['method']['enum'] = ['GET', 'PUT'];

        return $schema;
    },
    10,
    2
);

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'nextcloud') {
            return $defaults;
        }

        return wpct_plugin_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'default' => 'Nextcloud',
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'endpoint',
                        'label' => __('Filepath', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                        'pattern' => '.+.csv$',
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'method',
                        'label' => __('Method', 'forms-bridge'),
                        'type' => 'string',
                        'value' => 'PUT',
                        'required' => true,
                    ],
                ],
                'bridge' => [
                    'backend' => 'Nextcloud',
                ],
                'backend' => [
                    'name' => 'Nextcloud',
                    'headers' => [
                        [
                            'name' => 'Content-Type',
                            'value' => 'text/csv',
                        ],
                    ],
                    'authentication' => [
                        'type' => 'Basic',
                        'client_id' => '',
                        'client_secret' => '',
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
        if (strpos($template_id, 'nextcloud-') !== 0) {
            return $data;
        }

        return $data;
    },
    10,
    2
);
