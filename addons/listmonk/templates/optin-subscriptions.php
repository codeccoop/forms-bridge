<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Opt-in Subscriptions', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will convert form submissions into new list subscriptions with a double opt-in confirmation check.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/subscribers',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'status',
            'label' => __('Subscription status', 'forms-bridge'),
            'type' => 'options',
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
            'default' => __('Opt-in subscriptions', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Opt-in subscriptions', 'forms-bridge'),
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
                'value' => '0',
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
