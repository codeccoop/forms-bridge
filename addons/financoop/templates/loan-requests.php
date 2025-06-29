<?php

use FORMS_BRIDGE\Addon;
use FORMS_BRIDGE\Form_Bridge_Template_Exception;

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name !== 'financoop-loan-requests') {
            return $data;
        }

        $endpoint = implode(
            '/',
            array_slice(explode('/', $data['bridge']['endpoint']), 0, 4)
        );
        $campaign = Addon::fetch(
            'financoop',
            $data['backend'],
            $endpoint,
            null
        );

        if (empty($campaign)) {
            throw new Form_Bridge_Template_Exception(
                'financoop_api_error',
                __('Can\'t fetch campaign data', 'forms-bridge'),
                ['status' => 500]
            );
        }

        $loan_index = array_search(
            'loan_amount',
            array_column($data['form']['fields'], 'name')
        );

        $loan_field = &$data['form']['fields'][$loan_index];

        $min = $campaign['minimal_loan_amount'];
        if (!empty($min)) {
            $loan_field['min'] = $min;
            $loan_field['default'] = $min;
        }

        $max = $campaign['maximal_loan_amount'];
        if (!empty($max)) {
            $loan_field['max'] = $max;
        }

        return $data;
    },
    10,
    2
);

global $forms_bridge_iso2_countries;

return [
    'title' => __('Loan Requests', 'forms-bridge'),
    'description' => __(
        'Loans form template. The resulting bridge will convert form submissions into loan requests.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/campaign/{campaign_id}/loan_request',
        ],
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
            [
                [
                    'from' => 'country',
                    'to' => 'country',
                    'cast' => 'null',
                ],
            ],
        ],
        'custom_fields' => [
            [
                'name' => 'lang',
                'value' => '$locale',
            ],
        ],
        'workflow' => ['financoop-iso2-country-code', 'financoop-vat-id'],
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
                    global $forms_bridge_iso2_countries;
                    return [
                        'value' => $country_code,
                        'label' => $forms_bridge_iso2_countries[$country_code],
                    ];
                }, array_keys($forms_bridge_iso2_countries)),
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
