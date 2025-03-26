<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Bigin Company Contact', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#backend',
            'name' => 'name',
            'default' => 'Zoho Bigin API',
        ],
        [
            'ref' => '#credential',
            'name' => 'organization_id',
            'label' => __('Organization ID', 'form-bridge'),
            'description' => __(
                'From your organization dashboard, expand the profile sidebar and click on the copy user ID icon to get your organization ID.',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#credential',
            'name' => 'client_id',
            'label' => __('Client ID', 'forms-bridge'),
            'description' => __(
                'You have to create a Self-Client Application on the Zoho Developer Console and get the Client ID',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#credential',
            'name' => 'client_secret',
            'label' => __('Client secret', 'forms-bridge'),
            'description' => __(
                'You have to create a Self-Client Application on the Zoho Developer Console and get the Client Secret',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Endpoint', 'forms-bridge'),
            'type' => 'string',
            'value' => '/bigin/v2/Contacts',
        ],
        [
            'ref' => '#bridge',
            'name' => 'scope',
            'label' => __('Scope', 'forms-bridge'),
            'type' => 'string',
            'value' =>
                'ZohoBigin.modules.accounts.CREATE,ZohoBigin.modules.contacts.CREATE',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Companies', 'forms-bridge'),
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
                'label' => __('Street', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_Code',
                'label' => __('Postal code', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_City',
                'label' => __('City', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_State',
                'label' => __('State', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_Country',
                'label' => __('Country', 'forms-bridge'),
                'type' => 'text',
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
                'name' => 'Title',
                'label' => __('Title', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'text',
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
        'endpoint' => '/bigin/v2/Contacts',
        'scope' =>
            'ZohoBigin.modules.accounts.CREATE,ZohoBigin.modules.contacts.CREATE',
        'workflow' => ['zoho-bigin-account-name'],
    ],
    'backend' => [
        'base_url' => 'https://www.zohoapis.com',
        'headers' => [
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
];
