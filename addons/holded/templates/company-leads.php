<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Company Leads', 'forms-bridge'),
    'description' => __(
        'Lead form template. The resulting bridge will convert form submissions into leads linked to new companies.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Leads', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/crm/v1/leads',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'funnelId',
            'label' => __('Funnel', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                'endpoint' => '/api/crm/v1/funnels',
                'finger' => [
                    'value' => '[].id',
                    'label' => '[].name',
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'value',
            'label' => __('Lead value', 'forms-bridge'),
            'description' => __(
                'Estimated deal value in currency units',
                'forms-bridge'
            ),
            'type' => 'number',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'potential',
            'description' => __(
                'Deal potential as a percentage',
                'forms-bridge'
            ),
            'label' => __('Lead potential (%)', 'forms-bridge'),
            'type' => 'number',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tags',
            'label' => __('Contact tags', 'forms-bridge'),
            'description' => __('Tags separated by commas', 'forms-bridge'),
            'type' => 'text',
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/crm/v1/leads',
        'custom_fields' => [
            [
                'name' => 'type',
                'value' => 'lead',
            ],
            [
                'name' => 'defaults.language',
                'value' => '$locale',
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
                [
                    'from' => '?tags',
                    'to' => 'lead_tags',
                    'cast' => 'inherit',
                ],
            ],
            [
                [
                    'from' => 'country',
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
                    'from' => '?lead_tags',
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
