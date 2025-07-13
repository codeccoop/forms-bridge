<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Service Quotations', 'forms-bridge'),
    'description' => __(
        'Service quotations form template. The resulting bridge will convert form submissions into quotations linked to new contacts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Service Quotations', 'forms-bridge'),
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
            'name' => 'serviceId',
            'label' => __('Service', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                'endpoint' => '/api/invoicing/v1/services',
                'finger' => [
                    'value' => '[].id',
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
                'name' => 'isperson',
                'value' => '1',
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
                    'from' => 'isperson',
                    'to' => 'isperson',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'tags',
                    'to' => 'quotation_tags',
                    'cast' => 'inherit',
                ],
                [
                    'from' => 'your-name',
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
                    'from' => 'serviceId',
                    'to' => 'items[0].serviceId',
                    'cast' => 'string',
                ],
                [
                    'from' => 'quantity',
                    'to' => 'items[0].units',
                    'cast' => 'integer',
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
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'your-name',
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
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'address',
                'type' => 'text',
            ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'postalCode',
                'type' => 'text',
            ],
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
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
        ],
    ],
];
