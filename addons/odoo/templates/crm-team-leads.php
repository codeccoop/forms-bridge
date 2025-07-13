<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('CRM Team Leads', 'forms-bridge'),
    'description' => __(
        'Team lead form template. The resulting bridge will convert form submissions into leads linked to new contacts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('CRM Team Leads', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'crm.lead',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'team_id',
            'label' => __('Owner team', 'forms-bridge'),
            'description' => __(
                'Name of the owner team of the lead',
                'forms-bridge'
            ),
            'type' => 'options',
            'options' => [
                'endpoint' => 'crm.team',
                'finger' => [
                    'value' => 'result[].id',
                    'label' => 'result[].name',
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'lead_name',
            'label' => __('Lead name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => __('Web Lead', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'priority',
            'label' => __('Priority', 'forms-bridge'),
            'type' => 'number',
            'min' => 0,
            'max' => 3,
            'default' => 1,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'expected_revenue',
            'label' => __('Expected revenue', 'forms-bridge'),
            'type' => 'number',
            'min' => 0,
            'default' => 0,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tag_ids',
            'label' => __('Lead tags', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                'endpoint' => 'crm.tag',
                'finger' => [
                    'value' => 'result[].id',
                    'label' => 'result[].name',
                ],
            ],
            'is_multi' => true,
        ],
    ],
    'bridge' => [
        'endpoint' => 'crm.lead',
        'mutations' => [
            [
                [
                    'from' => 'team_id',
                    'to' => 'team_id',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'contact_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => '?priority',
                    'to' => 'priority',
                    'cast' => 'string',
                ],
                [
                    'from' => '?expected_revenue',
                    'to' => 'expected_revenue',
                    'cast' => 'number',
                ],
            ],
            [
                [
                    'from' => 'lead_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => ['crm-contact'],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'contact_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Your email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Your phone', 'forms-bridge'),
                'name' => 'phone',
                'type' => 'text',
            ],
            [
                'label' => __('Comments', 'forms-bridge'),
                'name' => 'description',
                'type' => 'textarea',
                'required' => true,
            ],
        ],
    ],
];
