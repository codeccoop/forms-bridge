<?php

use FORMS_BRIDGE\Finan_Coop_Addon;

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_odoo_countries;

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name !== 'financoop-loan-request') {
            return $data;
        }

        $campaign_id = $data['bridge']['campaign_id'];
        $backend_params = $data['backend'];

        // $campaign = Finan_Coop_Addon::fetch_campaign($campaign_id, $backend_params);

        // if (is_wp_error($campaign)) {
        //     throw $campaign;
        // }

        $loan_index = array_search(
            'loan_amount',
            array_column($data['form']['fields'], 'name')
        );

        $loan_field = &$data['form']['fields'][$loan_index];

        $loan_field['min'] = 300;
        // $loan_field['step'] = 300;

        return $data;
    },
    10,
    2
);

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'financoop-loan-request') {
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

        $vat_locale = strtoupper(substr($payload['vat'], 0, 2));

        if (!isset($forms_bridge_odoo_countries[$vat_locale])) {
            $payload['vat'] =
                strtoupper($payload['country_code']) . $payload['vat'];
        }

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('FinanCoop Loan Request', 'forms-bridge'),
    'bridge' => [
        'endpoint' => '/api/campaign/{campaign_id}/loan_request',
        'mappers' => [
            [
                'from' => 'loan_amount',
                'to' => 'loan_amount',
                'cast' => 'integer',
            ],
        ],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Loan amount', 'forms-bridge'),
                'name' => 'loan_amount',
                'type' => 'number',
                'required' => true,
                'min' => 0,
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
            // [
            //     'label' => __('City', 'forms-bridge'),
            //     'name' => 'city',
            //     'type' => 'text',
            //     'required' => true,
            // ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip_code',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ],
];
