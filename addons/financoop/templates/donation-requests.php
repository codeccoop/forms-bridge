<?php

use FORMS_BRIDGE\Addon;
use FORMS_BRIDGE\Form_Bridge_Template_Exception;

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name !== 'financoop-donation-requests') {
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

        $donation_index = array_search(
            'donation_amount',
            array_column($data['form']['fields'], 'name')
        );

        $donation_field = &$data['form']['fields'][$donation_index];

        $min = $campaign['minimal_donation_amount'];
        if (!empty($min)) {
            $donation_field['min'] = $min;
            $donation_field['default'] = $min;
        }

        return $data;
    },
    10,
    2
);

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
        //     'type' => 'options',
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
