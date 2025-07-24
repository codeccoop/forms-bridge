<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_schema',
    function ($schema, $addon) {
        if ($addon !== 'bigin') {
            return $schema;
        }

        return apply_filters('forms_bridge_template_schema', $schema, 'zoho');
    },
    10,
    2
);

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'bigin') {
            return $defaults;
        }

        $defaults = apply_filters(
            'forms_bridge_template_defaults',
            $defaults,
            'zoho',
            $schema
        );

        return wpct_plugin_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#credential',
                        'name' => 'scope',
                        'value' =>
                            'ZohoBigin.modules.ALL,ZohoBigin.settings.layouts.READ,ZohoBigin.users.READ',
                    ],
                ],
                'credential' => [
                    'scope' =>
                        'ZohoBigin.modules.ALL,ZohoBigin.settings.layouts.READ,ZohoBigin.users.READ',
                ],
            ],
            $defaults,
            $schema
        );
    },
    20,
    3
);

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_id) {
        if (strpos($template_id, 'bigin-') !== 0) {
            return $data;
        }

        return apply_filters(
            'forms_bridge_template_data',
            $data,
            'zoho-' . $template_id
        );
    },
    10,
    2
);
