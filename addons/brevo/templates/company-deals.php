<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_country_phone_codes;

return [
    'title' => __('Company deals', 'forms-bridge'),
    'description' => __(
        'Quotation form templates. The resulting bridge will convert form submissions into deals on the sales pipeline linked new companies.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/v3/crm/deals',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'deal_name',
            'label' => __('Deal name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'deal_owner',
            'label' => __('Owner email', 'forms-bridge'),
            'description' => __(
                'Email of the owner user of the deal',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'pipeline',
            'label' => __('Pipeline', 'forms-bridge'),
            'type' => 'string',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company deals', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Company deals', 'forms-bridge'),
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
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/v3/crm/deals',
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
            [
                [
                    'from' => 'deal_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'pipeline',
                    'to' => 'attributes.pipeline',
                    'cast' => 'string',
                ],
                [
                    'from' => 'deal_owner',
                    'to' => 'attributes.deal_owner',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => [
            'linked-contact',
            'country-phone-code',
            'linked-company',
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
];
