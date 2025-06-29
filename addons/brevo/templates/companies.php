<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_country_phone_codes;

return [
    'title' => __('Companies', 'forms-bridge'),
    'description' => __(
        'Contact form template. The resulting bridge will convert form submissions into companies linked to new contacts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/v3/companies',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Companies', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Companies', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'company_name',
                'label' => __('Company', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'country',
                'label' => __('Country', 'forms-bridge'),
                'type' => 'options',
                'options' => array_map(function ($country) {
                    return [
                        'value' => $country,
                        'label' => $country,
                    ];
                }, array_values($forms_bridge_country_phone_codes)),
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => __('Phone', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'website',
                'label' => __('Website', 'forms-bridge'),
                'type' => 'url',
            ],
            [
                'name' => 'industry',
                'label' => __('Industry', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'email',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'fname',
                'label' => __('Your first name', 'forms-bridge'),
                'type' => 'text',
                'required' => false,
            ],
            [
                'name' => 'lname',
                'label' => __('Your last name', 'forms-bridge'),
                'type' => 'text',
            ],
        ],
    ],
    'backend' => [
        'base_url' => 'https://api.brevo.com',
        'headers' => [
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/v3/companies',
        'custom_fields' => [
            [
                'name' => 'attributes.LANGUAGE',
                'value' => '$locale',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'fname',
                    'to' => 'attributes.FNAME',
                    'cast' => 'string',
                ],
                [
                    'from' => 'lname',
                    'to' => 'attributes.LNAME',
                    'cast' => 'string',
                ],
            ],
            [],
            [
                [
                    'from' => 'country',
                    'to' => 'country',
                    'cast' => 'null',
                ],
                [
                    'from' => 'company_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'phone',
                    'to' => 'attributes.phone_number',
                    'cast' => 'string',
                ],
                [
                    'from' => 'website',
                    'to' => 'attributes.domain',
                    'cast' => 'string',
                ],
                [
                    'from' => 'industry',
                    'to' => 'attributes.industry',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => ['linked-contact', 'country-phone-code'],
    ],
];
