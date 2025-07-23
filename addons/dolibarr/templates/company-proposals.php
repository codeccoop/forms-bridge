<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Company Proposals', 'forms-bridge'),
    'description' => __(
        'Quotations form template. The resulting bridge will convert form submissions into quotations linked to new companies.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/index.php/proposals',
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
                    'label' => __('Other', 'forms-bridge'),
                    'value' => '100',
                ],
            ],
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'stcomm_id',
            'label' => __('Prospect status', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                [
                    'label' => __('Never contacted', 'forms-bridge'),
                    'value' => '0',
                ],
                [
                    'label' => __('To contact', 'forms-bridge'),
                    'value' => '1',
                ],
                [
                    'label' => __('Contact in progress', 'forms-bridge'),
                    'value' => '2',
                ],
                [
                    'label' => __('Contacted', 'forms-bridge'),
                    'value' => '3',
                ],
                [
                    'label' => __('Do not contact', 'forms-bridge'),
                    'value' => '-1',
                ],
            ],
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'fk_product',
            'label' => __('Product', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                'endpoint' => '/api/index.php/products',
                'finger' => [
                    'value' => '[].id',
                    'label' => '[].label',
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Proposals', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Company Proposals', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'quantity',
                'label' => __('Quantity', 'forms-bridge'),
                'type' => 'number',
                'required' => true,
                'default' => 1,
                'min' => 1,
            ],
            [
                'name' => 'company_name',
                'label' => __('Company name', 'forms-bridge'),
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
                'name' => 'firstname',
                'label' => __('First name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'lastname',
                'label' => __('Last name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/proposals',
        'custom_fields' => [
            [
                'name' => 'status',
                'value' => '1',
            ],
            [
                'name' => 'client',
                'value' => '2',
            ],
            [
                'name' => 'date',
                'value' => '$timestamp',
            ],
            [
                'name' => 'lines[0].product_type',
                'value' => '1',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'quantity',
                    'to' => 'lines[0].qty',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'fk_product',
                    'to' => 'lines[0].fk_product',
                    'cast' => 'integer',
                ],
            ],
            [],
            [
                [
                    'from' => 'company_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'email',
                    'to' => 'contact_email',
                    'cast' => 'copy',
                ],
            ],
            [
                [
                    'from' => 'socid',
                    'to' => 'order_socid',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'contact_email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'order_socid',
                    'to' => 'socid',
                    'cast' => 'integer',
                ],
            ],
        ],
        'workflow' => [
            'iso2-country-code',
            'country-id',
            'contact-socid',
            'contact-id',
        ],
    ],
];
