<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Bigin Deals', 'forms-bridge'),
    'description' => __(
        'Creates new deals on your Bigin pipelines',
        'forms-bridge'
    ),
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
            'description' => __(
                'You have to create a Self-Client Application on the Zoho Developer Console and get the Client ID',
                'forms-bridge'
            ),
        ],
        [
            'ref' => '#credential',
            'name' => 'client_secret',
            'label' => __('Client Secret', 'forms-bridge'),
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
            'value' => '/bigin/v2/Pipelines',
        ],
        [
            'ref' => '#bridge',
            'name' => 'scope',
            'label' => __('Scope', 'forms-bridge'),
            'type' => 'string',
            'value' =>
                'ZohoBigin.modules.contacts.CREATE,ZohoBigin.modules.accounts.CREATE,ZohoBigin.modules.pipelines.CREATE',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Bigin Deals', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'Owner',
            'label' => __('Owner ID', 'forms-bridge'),
            'descritpion' => __(
                'ID of the owner user of the deal',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'Deal_Name',
            'label' => __('Deal name', 'forms-bridge'),
            'description' => __('Name of the pipeline deals', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'Stage',
            'label' => __('Deal stage', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'value' => 'Qualification',
                    'label' => __('Qualification', 'forms-bridge'),
                ],
                [
                    'value' => 'Needs Analysis',
                    'label' => __('Needs Analysis', 'forms-bridge'),
                ],
                [
                    'value' => 'Proposal/Price Quote',
                    'label' => __('Proposal/Price Quote', 'forms-bridge'),
                ],
                [
                    'value' => 'Negotation/Review',
                    'label' => __('Negotiation/Review', 'forms-bridge'),
                ],
                [
                    'value' => 'Closed Won',
                    'label' => __('Closed Won', 'forms-bridge'),
                ],
                [
                    'value' => 'Closed Lost',
                    'label' => __('Closed Lost', 'forms-bridge'),
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'Sub_Pipeline',
            'label' => __('Pipeline name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'Tag',
            'label' => __('Deal tags', 'forms-bridge'),
            'description' => __(
                'Tag names separated by commas',
                'forms-bridge'
            ),
            'type' => 'string',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'Owner',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'Deal_Name',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'Stage',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'Sub_Pipeline',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'Tag',
                'type' => 'hidden',
            ],
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
    'backend' => [
        'base_url' => 'https://www.zohoapis.com',
        'headers' => [
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/bigin/v2/Pipelines',
        'scope' =>
            'ZohoBigin.modules.contacts.CREATE,ZohoBigin.modules.accounts.CREATE,ZohoBigin.modules.pipelines.CREATE',
        'workflow' => [
            'zoho-bigin-account-name',
            'zoho-bigin-contact-name',
            'zoho-bigin-tags',
        ],
    ],
];
