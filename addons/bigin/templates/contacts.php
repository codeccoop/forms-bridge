<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Contacts', 'forms-bridge'),
    'description' => __(
        'Contact form template. The resulting bridge will convert form submissions into contacts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/bigin/v2/Contacts/upsert',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Owner.id',
            'label' => __('Owner ID', 'forms-bridge'),
            'description' => __(
                'ID of the owner user of the contact',
                'forms-bridge'
            ),
            'type' => 'select',
            'options' => [
                'endpoint' => '/bigin/v2/users',
                'finger' => [
                    'value' => 'users[].id',
                    'label' => 'users[].full_name',
                ],
            ],
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Contacts', 'forms-bridge'),
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'First_Name',
                'label' => __('First name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Last_Name',
                'label' => __('Last name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'Phone',
                'label' => __('Phone', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Description',
                'label' => __('Comments', 'forms-bridge'),
                'type' => 'textarea',
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/bigin/v2/Contacts/upsert',
    ],
];
