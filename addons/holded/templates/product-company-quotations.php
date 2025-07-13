<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Product Company Quotations', 'forms-bridge'),
    'description' => __(
        'Product quotations form template. The resulting bridge will convert form submissions into quotations linked to new companies.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Product Company Quotations', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/invoicing/v1/documents/estimate',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tags',
            'label' => __('Tags', 'forms-bridge'),
            'description' => __('Tags separated by commas', 'forms-bridge'),
            'type' => 'string',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'sku',
            'label' => __('Product', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                'endpoint' => '/api/invoicing/v1/products',
                'finger' => [
                    'value' => '[].sku',
                    'label' => '[].name',
                ],
            ],
            'required' => true,
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/invoicing/v1/documents/estimate',
        'custom_fields' => [
            [
                'name' => 'type',
                'value' => 'client',
            ],
            [
                'name' => 'defaults.language',
                'value' => '$locale',
            ],
            [
                'name' => 'date',
                'value' => '$timestamp',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'tags',
                    'to' => 'quotation_tags',
                    'cast' => 'inherit',
                ],
                [
                    'from' => 'company_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'code',
                    'to' => 'vatnumber',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'address',
                    'to' => 'billAddress.address',
                    'cast' => 'string',
                ],
                [
                    'from' => 'postalCode',
                    'to' => 'billAddress.postalCode',
                    'cast' => 'string',
                ],
                [
                    'from' => 'city',
                    'to' => 'billAddress.city',
                    'cast' => 'string',
                ],
                [
                    'from' => 'sku',
                    'to' => 'items[0].sku',
                    'cast' => 'string',
                ],
                [
                    'from' => 'quantity',
                    'to' => 'items[0].units',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'contact_name',
                    'to' => 'contactPersons[0].name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'email',
                    'to' => 'contactPersons[0].email',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'phone',
                    'to' => 'contactPersons[0].phone',
                    'cast' => 'copy',
                ],
            ],
            [
                [
                    'from' => 'country',
                    'to' => 'country',
                    'cast' => 'null',
                ],
                [
                    'from' => 'country_code',
                    'to' => 'countryCode',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'countryCode',
                    'to' => 'billAddress.countryCode',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'quotation_tags',
                    'to' => 'tags',
                    'cast' => 'inherit',
                ],
            ],
        ],
        'workflow' => ['iso2-country-code', 'prefix-vatnumber', 'contact-id'],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Quantity', 'forms-bridge'),
                'name' => 'quantity',
                'type' => 'number',
                'default' => 1,
                'min' => 0,
                'required' => true,
            ],
            [
                'label' => __('Company', 'forms-bridge'),
                'name' => 'company_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Tax ID', 'forms-bridge'),
                'name' => 'code',
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
                'name' => 'postalCode',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Country', 'forms-bridge'),
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
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'contact_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Your email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Your phone', 'forms-bridge'),
                'name' => 'phone',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ],
];
