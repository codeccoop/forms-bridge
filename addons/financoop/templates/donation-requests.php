<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Donation Requests', 'forms-bridge'),
    'description' => __(
        'Donations form template. The resulting bridge will convert form submissions into donation requests.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/campaign/{campaign_id}/donation_request',
        ],
        // [
        //     'ref' => '#bridge/custom_fields[]',
        //     'name' => 'tax_receipt_option',
        //     'label' => __('Tax receipt', 'forms-bridge'),
        //     'type' => 'select',
        //     'options' => [
        //         [
        //             'label' => 'foo',
        //             'value' => 'foo',
        //         ],
        //         [
        //             'label' => 'bar',
        //             'value' => 'bar',
        //         ],
        //     ],
        //     'required' => true,
        // ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Donation Requests', 'forms-bridge'),
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/campaign/{campaign_id}/donation_request',
        'mutations' => [
            [
                [
                    'from' => 'donation_amount',
                    'to' => 'donation_amount',
                    'cast' => 'integer',
                ],
            ],
            [],
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
                'label' => __('Donation amount', 'forms-bridge'),
                'name' => 'donation_amount',
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
