<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('FinanCoop Loan Request', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form/fields[]',
            'name' => 'partner_id',
            'label' => __('Partner ID', 'forms-bridge'),
            'type' => 'number',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'loan_type_id',
            'label' => __('Loan type ID', 'forms-bridge'),
            'type' => 'number',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'country_code',
            'label' => __('Country code', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/campaign/{campaign_id}/loan_request',
        'pipes' => [
            [
                'from' => 'partner_id',
                'to' => 'partner_id',
                'cast' => 'integer',
            ],
            [
                'from' => 'loan_amount',
                'to' => 'loan_amount',
                'cast' => 'integer',
            ],
            [
                'from' => 'loan_type_id',
                'to' => 'loan_type_id',
                'cast' => 'integer',
            ],
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'partner_id',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'loan_type_id',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'country_code',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'label' => __('Loan amount', 'forms-bridge'),
                'name' => 'loan_amount',
                'type' => 'number',
                'required' => true,
                'min' => 0,
            ],
            [
                'label' => __('First name', 'forms-bridge'),
                'name' => 'firstname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Last name', 'forms-bridge'),
                'name' => 'lastname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'address',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip_code',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Phone', 'forms-bridge'),
                'name' => 'phone',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ],
];
