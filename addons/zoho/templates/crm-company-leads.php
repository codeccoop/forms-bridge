<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('CRM Company Leads', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Owner.id',
            'label' => __('Owner ID', 'forms-bridge'),
            'description' => __(
                'ID of the owner user of the deal',
                'forms-bridge'
            ),
            'type' => 'string',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Lead_Source',
            'label' => __('Lead source', 'forms-bridge'),
            'description' => __(
                'Label to identify your website sourced leads',
                'forms-bridge'
            ),
            'type' => 'string',
            'default' => 'WordPress',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Lead_Status',
            'label' => __('Lead status', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Not Contacted', 'forms-bridge'),
                    'value' => 'Not Connected',
                ],
                [
                    'label' => __('Qualified', 'forms-bridge'),
                    'value' => 'Qualified',
                ],
                [
                    'label' => __('Not qualified', 'forms-bridge'),
                    'value' => 'Not Qualified',
                ],
                [
                    'label' => __('Pre-qualified', 'forms-bridge'),
                    'value' => 'Pre-Qualified',
                ],
                [
                    'label' => __('Attempted to Contact', 'forms-bridge'),
                    'value' => 'New Lead',
                ],
                [
                    'label' => __('Contact in Future', 'forms-bridge'),
                    'value' => 'Connected',
                ],
                [
                    'label' => __('Junk Lead', 'forms-bridge'),
                    'value' => 'Junk Lead',
                ],
                [
                    'label' => __('Lost Lead', 'forms-bridge'),
                    'value' => 'Lost Lead',
                ],
            ],
            'default' => 'Not Contacted',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Tag',
            'label' => __('Lead tags', 'forms-bridge'),
            'description' => __(
                'Tag names separated by commas',
                'forms-bridge'
            ),
            'type' => 'string',
        ],
        [
            'ref' => '#backend',
            'name' => 'name',
            'label' => __('Backend name', 'forms-bridge'),
            'type' => 'string',
            'default' => 'Zoho API',
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
                'You have to create a Self-Client Application on the <a href="https://api-console.zoho.com" target="_blank">Zoho Developer Console</a> and get the Client ID',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#credential',
            'name' => 'client_secret',
            'label' => __('Client Secret', 'forms-bridge'),
            'description' => __(
                'You have to create a Self-Client Application on the <a href="https://api-console.zoho.com" target="_blank">Zoho Developer Console</a> and get the Client Secret',
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
            'value' => '/crm/v7/Leads/upsert',
        ],
        [
            'ref' => '#bridge',
            'name' => 'scope',
            'label' => __('Scope', 'forms-bridge'),
            'type' => 'string',
            'value' =>
                'ZohoCRM.modules.leads.CREATE,ZohoCRM.modules.accounts.CREATE',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('CRM Company Leads', 'forms-bridge'),
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'Company',
                'label' => __('Company', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Street',
                'label' => __('Street', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Zip_Code',
                'label' => __('Postal code', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'City',
                'label' => __('City', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'State',
                'label' => __('State', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Country',
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
        'endpoint' => '/crm/v7/Leads/upsert',
        'scope' => 'ZohoCRM.modules.leads.CREATE',
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
