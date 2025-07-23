<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Thirdparties', 'forms-bridge'),
    'description' => __(
        'Contact form template. The resulting bridge will convert form submissions into thirdparties.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/index.php/thirdparties',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'client',
            'label' => __('Thirdparty status', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                [
                    'value' => '1',
                    'label' => __('Client', 'forms-bridge'),
                ],
                [
                    'value' => '2',
                    'label' => __('Prospect', 'forms-bridge'),
                ],
                [
                    'value' => '3',
                    'label' => __('Client/Prospect', 'forms-bridge'),
                ],
                [
                    'value' => '0',
                    'label' => __(
                        'Neither customer nor supplier',
                        'forms-bridge'
                    ),
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'typent_id',
            'label' => __('Thirdparty type', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                [
                    'label' => __('Large company', 'forms-bridge'),
                    'value' => '2',
                ],
                [
                    'label' => __('Medium company', 'forms-bridge'),
                    'value' => '3',
                ],
                [
                    'label' => __('Small company', 'forms-bridge'),
                    'value' => '4',
                ],
                [
                    'label' => __('Governmental', 'forms-bridge'),
                    'value' => '5',
                ],
                [
                    'label' => __('Startup', 'forms-bridge'),
                    'value' => '1',
                ],
                [
                    'label' => __('Retailer', 'forms-bridge'),
                    'value' => '7',
                ],
                [
                    'label' => __('Private individual', 'forms-bridge'),
                    'value' => '8',
                ],
                [
                    'label' => __('Other', 'forms-bridge'),
                    'value' => '100',
                ],
            ],
        ],
        // [
        //     'ref' => '#bridge/custom_fields[]',
        //     'name' => 'fournisseur',
        //     'label' => __('Provider', 'forms-bridge'),
        //     'type' => 'boolean',
        //     'default' => false,
        // ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Thirdparties', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Thirdparties', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'name',
                'label' => __('Name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'tva_intra',
                'label' => __('Tax ID', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => __('Phone', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'address',
                'label' => __('Address', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'zip',
                'label' => __('Postal code', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'town',
                'label' => __('City', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'country',
                'label' => __('Country', 'forms-bridge'),
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
                'name' => 'note_private',
                'label' => __('Comments', 'forms-bridge'),
                'type' => 'textarea',
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/thirdparties',
        'custom_fields' => [
            [
                'name' => 'status',
                'value' => '1',
            ],
        ],
        'workflow' => [
            'iso2-country-code',
            'country-id',
            'skip-if-thirdparty-exists',
            'next-client-code',
        ],
    ],
];
