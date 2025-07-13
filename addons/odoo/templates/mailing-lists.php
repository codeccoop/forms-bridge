<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Mailing Lists', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will convert form submissions into subscriptions to mailing lists.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Mailing Lists', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'mailing.contact',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'list_ids',
            'label' => __('Mailing lists', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                'endpoint' => 'mailing.list',
                'finger' => [
                    'value' => 'result[].id',
                    'label' => 'result[].name',
                ],
            ],
            'is_multi' => true,
            'required' => true,
        ],
    ],
    'bridge' => [
        'endpoint' => 'mailing.contact',
        'workflow' => ['mailing-contact'],
        'mutations' => [
            [
                [
                    'from' => 'first_name',
                    'to' => 'name[0]',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'last_name',
                    'to' => 'name[1]',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'name',
                    'to' => 'name',
                    'cast' => 'concat',
                ],
            ],
        ],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('First name', 'forms-bridge'),
                'name' => 'first_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Last name', 'forms-bridge'),
                'name' => 'last_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
        ],
    ],
];
