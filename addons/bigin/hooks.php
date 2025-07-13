<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_bridge_schema',
    function ($schema, $addon) {
        if ($addon !== 'bigin') {
            return $schema;
        }

        return apply_filters('forms_bridge_bridge_schema', $schema, 'zoho');
    },
    10,
    2
);

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
    function ($defaults, $schema, $addon) {
        if ($addon !== 'bigin') {
            return $defaults;
        }

        $defaults = apply_filters(
            'forms_bridge_template_defaults',
            $defaults,
            $schema,
            'zoho'
        );

        return wpct_plugin_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#credential',
                        'name' => 'scope',
                        'value' =>
                            'BiginCRM.modules.ALL,BiginCRM.settings.layouts.READ,BiginCRM.users.READ',
                    ],
                ],
                'credential' => [
                    'scope' =>
                        'BiginCRM.modules.ALL,BiginCRM.settings.layouts.READ,BiginCRM.users.READ',
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
    'forms_bridge_credential_schema',
    function ($schema, $addon) {
        if ($addon !== 'bigin') {
            return $schema;
        }

        $schema = apply_filters(
            'forms_bridge_credential_schema',
            $schema,
            'zoho'
        );

        $schema['properties']['scope']['default'] =
            'ZohoBigin.modules.ALL,ZohoBigin.settings.layouts.READ,ZohoBigin.users.READ';
        return $schema;
    },
    10,
    2
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
