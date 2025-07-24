<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Company Leads', 'forms-bridge'),
    'description' => __(
        'Lead form template. The resulting bridge will convert form submissions into company leads.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/crm/v7/Leads/upsert',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Owner.id',
            'label' => __('Owner', 'forms-bridge'),
            'description' => __(
                'Email of the owner user of the deal',
                'forms-bridge'
            ),
            'type' => 'select',
            'options' => [
                'endpoint' => '/crm/v7/users',
                'finger' => [
                    'value' => 'users[].id',
                    'label' => 'users[].full_name',
                ],
            ],
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Lead_Source',
            'label' => __('Lead source', 'forms-bridge'),
            'description' => __(
                'Label to identify your website sourced leads',
                'forms-bridge'
            ),
            'type' => 'text',
            'default' => 'WordPress',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Lead_Status',
            'label' => __('Lead status', 'forms-bridge'),
            'type' => 'select',
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
            'type' => 'text',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Leads', 'forms-bridge'),
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
];
