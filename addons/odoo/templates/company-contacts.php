<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Company Contacts', 'forms-bridge'),
    'description' => __(
        'Contact form for companies template. The resulting bridge will convert form submissions into new companies linked to contacts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Contacts', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'res.partner',
        ],
    ],
    'bridge' => [
        'endpoint' => 'res.partner',
        'workflow' => [
            'iso2-country-code',
            'vat-id',
            'country-id',
            'contact-company',
            'skip-if-partner-exists',
        ],
        'mutations' => [
            [
                [
                    'from' => 'company_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
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
            [],
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
        ],
        [],
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
        ],
    ],
];
