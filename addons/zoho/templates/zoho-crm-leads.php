<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_prune_empties',
    function ($prune, $bridge) {
        if ($bridge->template === 'zoho-crm-leads') {
            return true;
        }

        return $prune;
    },
    10,
    2
);

return [
    'title' => __('CRM Leads', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form/fields[]',
            'name' => 'Lead_Source',
            'label' => __('Lead source', 'forms-bridge'),
            'description' => __(
                'Label to identify your website sourced leads',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
            'default' => 'WordPress',
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'Lead_Status',
            'label' => __('Lead status', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => __('New lead', 'forms-bridge'),
                    'value' => 'New Lead',
                ],
                [
                    'label' => __('Connected', 'forms-bridge'),
                    'value' => 'Connected',
                ],
                [
                    'label' => __('Not connected', 'forms-bridge'),
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
                    'label' => __('Lead source', 'forms-bridge'),
                    'value' => 'Lead Source',
                ],
                [
                    'label' => __('Contact in future', 'forms-bridge'),
                    'value' => 'Contact in Future',
                ],
            ],
            'required' => true,
            'default' => 'New Lead',
        ],
        [
            'ref' => '#backend',
            'name' => 'name',
            'label' => __('Backend name', 'forms-bridge'),
            'type' => 'string',
            'default' => 'Zoho CRM API',
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
            'value' => '/crm/v7/Leads',
        ],
        [
            'ref' => '#bridge',
            'name' => 'scope',
            'label' => __('Scope', 'forms-bridge'),
            'type' => 'string',
            'value' => 'ZohoCRM.modules.leads.CREATE',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('CRM Leads', 'forms-bridge'),
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'Lead_Source',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'Lead_Status',
                'type' => 'hidden',
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
                'name' => 'Company',
                'label' => __('Company', 'forms-bridge'),
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
        'endpoint' => '/crm/v7/Leads',
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
