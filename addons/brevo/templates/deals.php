<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_country_phone_codes;

return [
    'title' => __('Deals', 'forms-bridge'),
    'description' => __(
        'Creates a contact and associates it with a deal',
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
            'default' => __('Deals', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Deals', 'forms-bridge'),
        'fields' => [
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
        'workflow' => ['brevo-linked-contact'],
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
