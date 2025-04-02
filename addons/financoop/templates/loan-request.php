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

        $campaign = \FORMS_BRIDGE\Finan_Coop_Addon::fetch_campaign(
            $campaign_id,
            $backend_params
        );

        if (is_wp_error($campaign)) {
            return;
        }

        $loan_index = array_search(
            'loan_amount',
            array_column($data['form']['fields'], 'name')
        );

        $loan_field = &$data['form']['fields'][$loan_index];

        if (!empty(($min = $campaign['minimal_loan_amount']))) {
            $loan_field['min'] = $min;
            $loan_field['default'] = $min;
        }

        if (!empty(($max = $campaign['maximal_loan_amount']))) {
            $loan_field['max'] = $max;
        }

        return $data;
    },
    10,
    2
);

global $forms_bridge_country_codes;

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
        'mutations' => [
            [
                [
                    'from' => 'loan_amount',
                    'to' => 'loan_amount',
                    'cast' => 'integer',
                ],
            ],
            [],
            [],
            [
                [
                    'from' => 'locale',
                    'to' => 'lang',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => [
            'forms-bridge-country-code',
            'financoop-vat-id',
            'forms-bridge-current-locale',
            'financoop-campaign-id',
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
                'name' => 'country',
                'type' => 'options',
                'options' => array_map(function ($country_code) {
                    global $forms_bridge_country_codes;
                    return [
                        'value' => $country_code,
                        'label' => $forms_bridge_country_codes[$country_code],
                    ];
                }, array_keys($forms_bridge_country_codes)),
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
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ],
];
