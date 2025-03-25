<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name !== 'financoop-loan-request') {
            return $data;
        }

        $campaign_id = $data['bridge']['campaign_id'];
        $backend_params = $data['backend'];

        // $campaign = \FORMS_BRIDGE\Finan_Coop_Addon::fetch_campaign($campaign_id, $backend_params);

        // if (is_wp_error($campaign)) {
        //     return;
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

global $forms_bridge_odoo_countries;

return [
    'title' => __('Loan Requests', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Loan Requests', 'forms-bridge'),
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/campaign/{campaign_id}/loan_request',
        'mappers' => [
            [
                'from' => 'loan_amount',
                'to' => 'loan_amount',
                'cast' => 'integer',
            ],
        ],
        'workflow' => ['financoop-country-code', 'financoop-vat-id'],
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
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip_code',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ],
];
