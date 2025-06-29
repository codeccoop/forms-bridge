<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Company Contacts', 'forms-bridge'),
    'description' => __(
        'Contact form for companies template. The resulting bridge will convert form submissions into new contacts linked to company accounts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/crm/v7/Contacts/upsert',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Owner.id',
            'label' => __('Owner', 'forms-bridge'),
            'description' => __(
                'Email of the owner user of the account',
                'forms-bridge'
            ),
            'type' => 'string',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Contacts', 'forms-bridge'),
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'Account_Name',
                'label' => __('Company name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Billing_Street',
                'label' => __('Address', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Billing_City',
                'label' => __('City', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Billing_Code',
                'label' => __('Zip code', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Billing_State',
                'label' => __('State', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Billing_Country',
                'label' => __('Country', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
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
                'name' => 'Title',
                'label' => __('Job position', 'forms-bridge'),
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
        'workflow' => ['contact-account'],
    ],
];
