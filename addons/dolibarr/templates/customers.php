<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Customers', 'forms-bridge'),
    'description' => __(
        'Contact form template. The resulting bridge will convert form submissions into customers.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/index.php/contacts',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'typent_id',
            'label' => __('Customer type', 'forms-bridge'),
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
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Customers', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Customers', 'forms-bridge'),
        'fields' => [
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
                'name' => 'contact_email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'poste',
                'label' => __('Job position', 'forms-bridge'),
                'type' => 'text',
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
        'endpoint' => '/api/index.php/contacts',
        'custom_fields' => [
            [
                'name' => 'status',
                'value' => '1',
            ],
            [
                'name' => 'client',
                'value' => '1',
            ],
        ],
        'mutations' => [
            [],
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
                    'from' => 'contact_email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
            ],
            [],
        ],
        'workflow' => [
            'iso2-country-code',
            'country-id',
            'contact-socid',
            'skip-if-contact-exists',
        ],
    ],
];
