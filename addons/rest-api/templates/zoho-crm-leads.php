<?php

if (!defined('ABSPATH')) {
    exit();
}

add_action(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'zoho-crm-leads') {
            $index = array_search(
                'Authorization',
                array_column($data['backend']['headers'], 'name')
            );

            $header = &$data['backend']['headers'][$index];
            $header['value'] = 'Zoho-oauthtoken ' . $header['value'];
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Zoho CRM Leads', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form/fields[]',
            'name' => 'Lead_Owner',
            'label' => __('Lead owner', 'forms-bridge'),
            'description' => __(
                'Email address of the owner of the lead',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
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
                    'label' => __('Connected', 'forms-bridge'),
                    'value' => 'Connected',
                ],
            ],
            'required' => true,
            'default' => 'Connected',
        ],
        [
            'ref' => '#backend',
            'name' => 'name',
            'label' => __('Backend name', 'forms-bridge'),
            'type' => 'string',
            'default' => 'Zoho CRM API',
        ],
        [
            'ref' => '#backend',
            'name' => 'base_url',
            'label' => __('Backend base URL', 'forms-bridge'),
            'type' => 'string',
            'value' => 'https://www.zohoapis.com/crm/v7',
        ],
        [
            'ref' => '#backend/headers[]',
            'name' => 'Authorization',
            'label' => __('OAuth grant token', 'forms-bridge'),
            'description' => __(
                'Do you have to create a Self-Client Application on the Zoho Developer Console and generates a grant token without caducity',
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
            'value' => '/v7/Leads',
        ],
        [
            'ref' => '#bridge',
            'name' => 'method',
            'label' => __('Method', 'forms-bridge'),
            'type' => 'string',
            'value' => 'POST',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'Lead_Owner',
                'type' => 'hidden',
                'required' => true,
            ],
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
        'endpoint' => '/v7/Leads',
    ],
    'backend' => [
        'base_url' => 'https://www.zohoapis.com/crm/v7',
        'headers' => [
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
];
