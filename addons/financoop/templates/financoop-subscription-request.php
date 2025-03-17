<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name !== 'financoop-subscription-request') {
            return $data;
        }

        $campaign_id = $data['bridge']['campaign_id'];
        $backend_params = $data['backend'];

        // $campaign = Finan_Coop_Addon::fetch_campaign($campaign_id, $backend_params);

        // if (is_wp_error($campaign)) {
        //     throw $campaign;
        // }

        $parts_index = array_search(
            'ordered_parts',
            array_column($data['form']['fields'], 'name')
        );

        $parts_field = &$data['form']['fields'][$parts_index];

        $parts_field['min'] = 300;
        $parts_field['step'] = 300;

        return $data;
    },
    10,
    2
);

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'financoop-subscription-request') {
            return $payload;
        }

        global $forms_bridge_odoo_countries;

        if (!isset($forms_bridge_odoo_countries[$payload['country_code']])) {
            $countries_by_label = array_reduce(
                array_keys($forms_bridge_odoo_countries),
                function ($labels, $country_code) {
                    global $forms_bridge_odoo_countries;
                    $label = $forms_bridge_odoo_countries[$country_code];
                    $labels[$label] = $country_code;
                    return $labels;
                },
                []
            );

            $payload['country_code'] =
                $countries_by_label[$payload['country_code']];
        }

        $payload['vat'] =
            strtoupper($payload['country_code']) . $payload['vat'];

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('FinanCoop Subscription Request', 'forms-bridge'),
    'bridge' => [
        'endpoint' => '/api/campaign/{campaign_id}/subscription_request',
        'mappers' => [
            [
                'from' => 'ordered_parts',
                'to' => 'ordered_parts',
                'cast' => 'integer',
            ],
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'source',
                'type' => 'hidden',
                'required' => true,
                'value' => 'website',
            ],
            [
                'name' => 'type',
                'type' => 'hidden',
                'required' => true,
                'value' => 'increase',
            ],
            [
                'label' => __('Ordered parts', 'forms-bridge'),
                'name' => 'ordered_parts',
                'type' => 'number',
                'required' => true,
                'min' => 1,
            ],
            [
                'label' => __('First name', 'forms-bridge'),
                'name' => 'firstname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Last name', 'forms-bridge'),
                'name' => 'lastname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('ID number', 'forms-bridge'),
                'name' => 'vat',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Nationality', 'forms-bridge'),
                'name' => 'country_code',
                'type' => 'options',
                'options' => array_map(function ($country_code) {
                    global $forms_bridge_odoo_countries;
                    return [
                        'value' => $country_code,
                        'label' => $forms_bridge_odoo_countries[$country_code],
                    ];
                }, array_keys($forms_bridge_odoo_countries)),
                'required' => true,
            ],
            [
                'label' => __('Email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Phone', 'forms-bridge'),
                'name' => 'phone',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'address',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip_code',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ],
];
