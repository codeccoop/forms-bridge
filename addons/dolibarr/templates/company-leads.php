<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Company Leads', 'forms-bridge'),
    'description' => __(
        'Leads form template. The resulting bridge will convert form submissions into company lead projects linked to new contacts.',
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
            'type' => 'select',
            'options' => [
                'endpoint' => '/api/index.php/users',
                'finger' => ['value' => '[].id', 'label' => '[].email'],
            ],
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'typent_id',
            'label' => __('Company type', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                [
                    'label' => __('Large company', 'forms-bridge'),
                    'value' => '2',
                ],
                [
                    'label' => __('Medium company', 'forms-bridge'),
                    'value' => '3',
                ],
                [
                    'label' => __('Small company', 'forms-bridge'),
                    'value' => '4',
                ],
                [
                    'label' => __('Governmental', 'forms-bridge'),
                    'value' => '5',
                ],
                [
                    'label' => __('Startup', 'forms-bridge'),
                    'value' => '1',
                ],
                [
                    'label' => __('Other', 'forms-bridge'),
                    'value' => '100',
                ],
            ],
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'stcomm_id',
            'label' => __('Prospect status', 'forms-bridge'),
            'type' => 'select',
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
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'opp_status',
            'label' => __('Lead status', 'forms-bridge'),
            'type' => 'select',
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
            'default' => __('Company Leads', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Company Leads', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'company_name',
                'label' => __('Company name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'idprof1',
                'label' => __('Tax ID', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'address',
                'label' => __('Address', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'zip',
                'label' => __('Postal code', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'town',
                'label' => __('City', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'country',
                'label' => __('Country', 'forms-bridge'),
                'type' => 'select',
                'options' => array_map(function ($country_code) {
                    global $forms_bridge_iso2_countries;
                    return [
                        'value' => $country_code,
                        'label' => $forms_bridge_iso2_countries[$country_code],
                    ];
                }, array_keys($forms_bridge_iso2_countries)),
                'required' => true,
            ],
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
                'name' => 'poste',
                'label' => __('Job position', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
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
                    'from' => '?userownerid',
                    'to' => 'userid',
                    'cast' => 'integer',
                ],
            ],
            [],
            [
                [
                    'from' => 'company_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'name',
                    'to' => 'title',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'email',
                    'to' => 'contact_email',
                    'cast' => 'copy',
                ],
            ],
            [
                [
                    'from' => 'socid',
                    'to' => 'lead_socid',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'contact_email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'lead_socid',
                    'to' => 'socid',
                    'cast' => 'integer',
                ],
            ],
        ],
        'workflow' => [
            'iso2-country-code',
            'country-id',
            'contact-socid',
            'contact-id',
            'next-project-ref',
        ],
    ],
];
