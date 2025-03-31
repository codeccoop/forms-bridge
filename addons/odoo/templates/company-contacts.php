<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_country_codes;

return [
    'title' => __('Company Contacts', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Contacts', 'forms-bridge'),
        ],
    ],
    'bridge' => [
        'model' => 'res.partner',
        'workflow' => [
            'odoo-country-code',
            'odoo-vat-id',
            'odoo-contact-company-id',
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
                'label' => __('Tax ID'),
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
                'type' => 'options',
                'options' => array_map(function ($country_code) {
                    global $forms_bridge_country_codes;
                    return [
                        'value' => $country_code,
                        'label' => $forms_bridge_country_codes[$country_code],
                    ];
                }, array_keys($forms_bridge_country_codes)),
                'required' => true,
            ],
            [
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'name',
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
