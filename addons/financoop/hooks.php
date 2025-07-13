<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_bridge_schema',
    function ($schema, $addon) {
        if ($addon !== 'financoop') {
            return $schema;
        }

        $schema['properties']['method']['enum'] = 'POST';
        return $schema;
    },
    10,
    2
);

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'financoop') {
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
                        'ref' => '#bridge',
                        'name' => 'method',
                        'value' => 'POST',
                    ],
                    [
                        'ref' => '#bridge/custom_fields[]',
                        'name' => 'campaign_id',
                        'label' => __('Campaign ID', 'forms-bridge'),
                        'type' => 'number',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'default' => 'FinanCoop',
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'X-Odoo-Db',
                        'label' => 'Database',
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'X-Odoo-Username',
                        'label' => 'Username',
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'X-Odoo-Api-Key',
                        'label' => 'API Key',
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
                    'backend' => 'FinanCoop',
                    'method' => 'POST',
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
        if (strpos($template_id, 'financoop-') !== 0) {
            return $data;
        }

        $index = array_search(
            'campaign_id',
            array_column($data['bridge']['custom_fields'], 'name')
        );

        if ($index !== false) {
            $campaign_id = $data['bridge']['custom_fields'][$index]['value'];

            $data['bridge']['endpoint'] = preg_replace(
                '/\{campaign_id\}/',
                $campaign_id,
                $data['bridge']['endpoint']
            );

            array_splice($data['bridge']['custom_fields'], $index, 1);

            $data['bridge']['custom_fields'] = array_values(
                $data['bridge']['custom_fields']
            );
        } else {
            return new WP_Error(
                'invalid_fields',
                __(
                    'Financoop template requireds the field $campaign_id',
                    'forms-bridge'
                ),
                ['status' => 400]
            );
        }

        $endpoint = implode(
            '/',
            array_slice(explode('/', $data['bridge']['endpoint']), 0, 4)
        );

        $addon = FBAPI::get_addon('financoop');
        $campaign = $addon->fetch(
            'financoop',
            $data['backend'],
            $endpoint,
            null
        );

        if (empty($campaign) || is_wp_error($campaign)) {
            return new WP_Error(
                'financoop_api_error',
                __('Can\'t fetch campaign data', 'forms-bridge'),
                ['status' => 500]
            );
        }

        $field_names = array_column($data['form']['fields'], 'name');

        $index = array_search('donation_amount', $field_names);
        if ($index !== false) {
            $field = &$data['form']['fields'][$index];

            $min = $campaign['minimal_donation_amount'];
            if (!empty($min)) {
                $field['min'] = $min;
                $field['default'] = $min;
            }
        }

        $index = array_search('loan_amount', $field_names);
        if ($index !== false) {
            $field = &$data['form']['fields'][$index];

            $min = $campaign['minimal_loan_amount'];
            if (!empty($min)) {
                $field['min'] = $min;
                $field['default'] = $min;
            }

            $max = $campaign['maximal_loan_amount'];
            if (!empty($max)) {
                $field['max'] = $max;
            }
        }

        $index = array_search('ordered_parts', $field_names);
        if ($index !== false) {
            $field = &$data['form']['fields'][$index];

            $min = $campaign['minimal_subscription_amount'];
            if (!empty($min)) {
                $field['min'] = $min;
                $field['step'] = $min;
                $field['default'] = $min;
            }

            $max = $campaign['maximal_subscription_amount'];
            if (!empty($max)) {
                $field['max'] = $max;
            }
        }

        return $data;
    },
    10,
    2
);
