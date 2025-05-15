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
            'value' => '/crm/v7/Contacts/upsert',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('CRM Contacts', 'forms-bridge'),
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
        'endpoint' => '/crm/v7/Contacts/upsert',
        'scope' => 'ZohoCRM.modules.ALL',
    ],
];
