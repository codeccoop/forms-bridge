<?php

if (!defined('ABSPATH')) {
    exit();
}

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
                    'to' => 'country_code',
                    'cast' => 'string',
                ],
            ],
        ],
        'custom_fields' => [
            [
                'name' => 'lang',
                'value' => '$locale',
            ],
        ],
        'workflow' => ['iso2-country-code', 'vat-id'],
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
                'type' => 'select',
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
