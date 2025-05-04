<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Contacts', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Contacts', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'res.partner',
        ],
    ],
    'bridge' => [
        'endpoint' => 'res.partner',
        'custom_fields' => [
            [
                'name' => 'is_company',
                'value' => '0',
            ],
            [
                'name' => 'lang',
                'value' => '$locale',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'is_company',
                    'to' => 'is_company',
                    'cast' => 'boolean',
                ],
                [
                    'from' => 'your-name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'country',
                    'to' => 'country',
                    'cast' => 'null',
                ],
            ],
        ],
        'workflow' => [
            'forms-bridge-iso2-country-code',
            'odoo-country-id',
            'odoo-skip-if-partner-exists',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'your-name',
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
                'label' => __('Address', 'forms-bridge'),
                'name' => 'street',
                'type' => 'text',
            ],
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
            ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip',
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
