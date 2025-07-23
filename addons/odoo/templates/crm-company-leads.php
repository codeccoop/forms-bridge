<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('CRM Company Leads', 'forms-bridge'),
    'description' => __(
        'Leads form template. The resulting bridge will convert form submissions into leads linked to new companies.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('CRM Company Leads', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'crm.lead',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'user_id',
            'label' => __('Owner email', 'forms-bridge'),
            'description' => __(
                'Email of the owner user of the lead',
                'forms-bridge'
            ),
            'type' => 'select',
            'options' => [
                'endpoint' => 'res.users',
                'finger' => [
                    'value' => 'result[].id',
                    'label' => 'result[].name',
                ],
            ],
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'lead_name',
            'label' => __('Lead name', 'forms-bridge'),
            'type' => 'text',
            'required' => true,
            'default' => __('Web Lead', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'priority',
            'label' => __('Priority', 'forms-bridge'),
            'type' => 'number',
            'min' => 0,
            'max' => 3,
            'default' => 1,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'expected_revenue',
            'label' => __('Expected revenue', 'forms-bridge'),
            'type' => 'number',
            'min' => 0,
            'default' => 0,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tag_ids',
            'label' => __('Lead tags', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                'endpoint' => 'crm.tag',
                'finger' => [
                    'value' => 'result[].id',
                    'label' => 'result[].name',
                ],
            ],
            'is_multi' => true,
        ],
    ],
    'bridge' => [
        'endpoint' => 'crm.lead',
        'mutations' => [
            [
                [
                    'from' => '?user_id',
                    'to' => 'user_id',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'company_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => '?priority',
                    'to' => 'priority',
                    'cast' => 'string',
                ],
                [
                    'from' => '?expected_revenue',
                    'to' => 'expected_revenue',
                    'cast' => 'number',
                ],
            ],
            [],
            [],
            [
                [
                    'from' => 'email',
                    'to' => 'contact_email',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'phone',
                    'to' => 'contact_phone',
                    'cast' => 'copy',
                ],
            ],
            [
                [
                    'from' => 'contact_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'contact_email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
                [
                    'from' => 'contact_phone',
                    'to' => 'phone',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'lead_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => [
            'iso2-country-code',
            'vat-id',
            'country-id',
            'contact-company',
            'crm-contact',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Company name', 'forms-bridge'),
                'name' => 'company_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Tax ID', 'forms-bridge'),
                'name' => 'vat',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'street',
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
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip',
                'type' => 'text',
                'required' => true,
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
                'label' => __('Job position', 'forms-bridge'),
                'name' => 'function',
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
            ],
            [
                'label' => __('Comments', 'forms-bridge'),
                'name' => 'description',
                'type' => 'textarea',
                'required' => true,
            ],
        ],
    ],
];
