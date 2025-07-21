<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Subscription', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will convert form submissions into new list subscriptions.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/3.0/lists/{list_id}/members',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'status',
            'label' => __('Subscription status', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                [
                    'label' => __('Subscribed', 'forms-bridge'),
                    'value' => 'subscribed',
                ],
                [
                    'label' => __('Unsubscribed', 'forms-bridge'),
                    'value' => 'unsubscribed',
                ],
                [
                    'label' => __('Pending', 'forms-bridge'),
                    'value' => 'pending',
                ],
                [
                    'label' => __('Cleaned', 'forms-bridge'),
                    'value' => 'cleand',
                ],
                [
                    'label' => __('Transactional', 'forms-bridge'),
                    'value' => 'transactional',
                ],
            ],
            'default' => 'subscribed',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tags',
            'label' => __('Subscription tags', 'forms-bridge'),
            'description' => __(
                'Tag names separated by commas',
                'forms-bridge'
            ),
            'type' => 'text',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Subscription', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Subscription', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'email_address',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'fname',
                'label' => __('Your first name', 'forms-bridge'),
                'type' => 'text',
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
        'endpoint' => '/3.0/lists/{list_id}/members',
        'custom_fields' => [
            [
                'name' => 'language',
                'value' => '$locale',
            ],
            [
                'name' => 'ip_signup',
                'value' => '$ip_address',
            ],
            [
                'name' => 'timestamp_signup',
                'value' => '$iso_date',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'fname',
                    'to' => 'merge_fields.FNAME',
                    'cast' => 'string',
                ],
                [
                    'from' => 'lname',
                    'to' => 'merge_fields.LNAME',
                    'cast' => 'string',
                ],
            ],
        ],
    ],
];
