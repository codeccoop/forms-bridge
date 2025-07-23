<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Proposals', 'forms-bridge'),
    'description' => __(
        'Quotations form template. The resulting bridge will convert form submissions into quotations linked to new contacts.',
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
            'name' => 'stcomm_id',
            'label' => __('Prospect status', 'forms-bridge'),
            'required' => true,
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
            'default' => '0',
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
            'default' => __('Proposals', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Proposals', 'forms-bridge'),
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
                'name' => 'typent_id',
                'value' => '8',
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
            [],
            [
                [
                    'from' => 'firstname',
                    'to' => 'name[0]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'lastname',
                    'to' => 'name[1]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'name',
                    'to' => 'name',
                    'cast' => 'concat',
                ],
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
        ],
        'workflow' => ['iso2-country-code', 'country-id', 'contact-socid'],
    ],
];
