<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Subscriptions', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will convert form submissions into new list subscriptions.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/subscribers',
        ],
        [
            'ref' => '#bridge',
            'name' => 'method',
            'value' => 'POST',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'status',
            'label' => __('Subscription status', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                [
                    'label' => __('Enabled', 'forms-bridge'),
                    'value' => 'enabled',
                ],
                [
                    'label' => __('Disabled', 'forms-bridge'),
                    'value' => 'blocklisted',
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Subscriptions', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Subscriptions', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'your-email',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'your-name',
                'label' => __('Your name', 'forms-bridge'),
                'type' => 'text',
            ],
        ],
    ],
    'backend' => [
        'headers' => [
            [
                'name' => 'Content-Type',
                'value' => 'application/json',
            ],
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/api/subscribers',
        'custom_fields' => [
            [
                'name' => 'attribs.locale',
                'value' => '$locale',
            ],
            [
                'name' => 'preconfirm_subscriptions',
                'value' => '1',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'your-email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
                [
                    'from' => 'your-name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'preconfirm_subscriptions',
                    'to' => 'preconfirm_subscriptions',
                    'cast' => 'boolean',
                ],
            ],
        ],
    ],
];
