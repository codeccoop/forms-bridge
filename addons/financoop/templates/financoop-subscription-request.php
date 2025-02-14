<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('FinanCoop Subscription Request', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form/fields[]',
            'name' => 'partner_id',
            'label' => __('Partner ID', 'forms-bridge'),
            'type' => 'number',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'ordered_parts',
            'label' => __('Ordered parts', 'forms-bridge'),
            'type' => 'number',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'share_product_id',
            'label' => __('Share product ID', 'forms-bridge'),
            'type' => 'number',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'source',
            'label' => __('Source', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'country_code',
            'label' => __('Country code', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/campaign/{campaign_id}/subscription_request',
        'pipes' => [
            [
                'from' => 'partner_id',
                'to' => 'partner_id',
                'cast' => 'integer',
            ],
            [
                'from' => 'ordered_parts',
                'to' => 'ordered_parts',
                'cast' => 'integer',
            ],
            [
                'from' => 'share_product_id',
                'to' => 'share_product_id',
                'cast' => 'integer',
            ],
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'partner_id',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'ordered_parts',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'share_product_id',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'source',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'type',
                'type' => 'hidden',
                'required' => true,
                'value' => 'increase',
            ],
            [
                'name' => 'country_code',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'label' => __('First name', 'forms-bridge'),
                'name' => 'firstname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Last name', 'forms-bridge'),
                'name' => 'lastname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'address',
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
                'name' => 'zip_code',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Phone', 'forms-bridge'),
                'name' => 'phone',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ],
];
