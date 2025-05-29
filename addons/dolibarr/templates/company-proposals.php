<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_dolibarr_countries;

return [
    'title' => __('Company Quotations', 'forms-bridge'),
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
            'label' => __('Third party type', 'forms-bridge'),
            'type' => 'options',
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
            ],
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'stcomm_id',
            'label' => __('Prospect status', 'forms-bridge'),
            'required' => true,
            'type' => 'options',
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
            'default' => ' 0',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'fk_product',
            'label' => __('Product', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Quotations', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Company Quotations', 'forms-bridge'),
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
                'name' => 'country_id',
                'label' => __('Country', 'forms-bridge'),
                'type' => 'options',
                'options' => array_map(function ($country_id) {
                    global $forms_bridge_dolibarr_countries;
                    return [
                        'value' => $country_id,
                        'label' =>
                            $forms_bridge_dolibarr_countries[$country_id],
                    ];
                }, array_keys($forms_bridge_dolibarr_countries)),
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
        ],
        'mutations' => [
            [
                [
                    'from' => 'company_name',
                    'to' => 'name',
                    'cast' => 'string',
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
            [],
            [
                [
                    'from' => 'socid',
                    'to' => 'order_socid',
                    'cast' => 'copy',
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
            'dolibarr-country-id',
            'dolibarr-contact-socid',
            'dolibarr-contact-id',
        ],
    ],
];
