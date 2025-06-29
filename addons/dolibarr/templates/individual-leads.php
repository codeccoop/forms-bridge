<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Leads', 'forms-bridge'),
    'description' => __(
        'Lead form template. The resulting bridge will convert form submissions into lead projects linked to new contacts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/index.php/projects',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'userownerid',
            'label' => __('Owner', 'forms-bridge'),
            'description' => __('Owner user of the lead', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'stcomm_id',
            'label' => __('Prospect status', 'forms-bridge'),
            'required' => true,
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Never contacted', 'forms-bridge'),
                    'value' => '0',
                ],
                [
                    'label' => __('To contact', 'forms-bridge'),
                    'value' => '1',
                ],
                [
                    'label' => __('Contact in progress', 'forms-bridge'),
                    'value' => '2',
                ],
                [
                    'label' => __('Contacted', 'forms-bridge'),
                    'value' => '3',
                ],
                [
                    'label' => __('Do not contact', 'forms-bridge'),
                    'value' => '-1',
                ],
            ],
            'default' => '0',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'opp_status',
            'label' => __('Lead status', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Prospection', 'forms-bridge'),
                    'value' => '1',
                ],
                [
                    'label' => __('Qualification', 'forms-bridge'),
                    'value' => '2',
                ],
                [
                    'label' => __('Proposal', 'forms-bridge'),
                    'value' => '3',
                ],
                [
                    'label' => __('Negociation', 'forms-bridge'),
                    'value' => '4',
                ],
            ],
            'required' => true,
            'default' => '1',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'opp_amount',
            'label' => __('Lead amount', 'forms-bridge'),
            'type' => 'number',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Leads', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Leads', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'firstname',
                'label' => __('First name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'lastname',
                'label' => __('Last name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => __('Phone', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'note_private',
                'label' => __('Comments', 'forms-bridge'),
                'type' => 'textarea',
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/projects',
        'custom_fields' => [
            [
                'name' => 'status',
                'value' => '1',
            ],
            [
                'name' => 'typent_id',
                'value' => '8',
            ],
            [
                'name' => 'client',
                'value' => '2',
            ],
            [
                'name' => 'usage_opportunity',
                'value' => '1',
            ],
            [
                'name' => 'date_start',
                'value' => '$timestamp',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'firstname',
                    'to' => 'name[0]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'lastname',
                    'to' => 'name[1]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'name',
                    'to' => 'name',
                    'cast' => 'concat',
                ],
                [
                    'from' => 'name',
                    'to' => 'title',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'userownerid',
                    'to' => 'userid',
                    'cast' => 'integer',
                ],
            ],
        ],
        'workflow' => ['contact-socid', 'next-project-ref'],
    ],
];
