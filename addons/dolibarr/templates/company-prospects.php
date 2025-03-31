<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_dolibarr_countries;

return [
    'title' => __('Company Prospects', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Endpoint', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'value' => '/api/index.php/contacts',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Prospects', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
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
            'default' => ' 0',
        ],
    ],
    'form' => [
        'title' => __('Company Leads', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'status',
                'value' => '1',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'typent_id',
                'value' => '2',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'client',
                'value' => '2',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'stcomm_id',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'name',
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
                'name' => 'country_id',
                'label' => __('Country', 'forms-bridge'),
                'type' => 'options',
                'options' => array_map(function ($country_id) {
                    global $forms_bridge_dolibarr_countries;
                    return [
                        'value' => $country_id,
                        'label' =>
                            $forms_bridge_dolibarr_countries[$country_id],
                    ];
                }, array_keys($forms_bridge_dolibarr_countries)),
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
                'name' => 'note_public',
                'label' => __('Comments', 'forms-bridge'),
                'type' => 'textarea',
            ],
        ],
    ],
    'backend' => [
        'headers' => [
            'name' => 'Accept',
            'value' => 'application/json',
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/contacts',
        'method' => 'POST',
        'mutations' => [
            [
                [
                    'from' => 'status',
                    'to' => 'status',
                    'cast' => 'string',
                ],
                [
                    'from' => 'typent_id',
                    'to' => 'typent_id',
                    'cast' => 'string',
                ],
                [
                    'from' => 'client',
                    'to' => 'client',
                    'cast' => 'string',
                ],
                [
                    'from' => 'stcomm_id',
                    'to' => 'stcomm_id',
                    'cast' => 'string',
                ],
                [
                    'from' => 'email',
                    'to' => 'contact_email',
                    'cast' => 'copy',
                ],
            ],
            [],
            [],
            [
                [
                    'from' => 'contact_email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => [
            'dolibarr-country-id',
            'dolibarr-thirdparty-id',
            'dolibarr-skip-if-contact-exists',
        ],
    ],
];
