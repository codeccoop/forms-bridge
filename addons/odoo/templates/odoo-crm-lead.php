<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Odoo CRM Lead', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form/fields[]',
            'name' => 'user_email',
            'label' => __('User email', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'name',
            'label' => __('Lead name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        // [
        //     'ref' => '#form/fields[]',
        //     'name' => 'type',
        //     'label' => __('Lead type', 'forms-bridge'),
        //     'type' => 'string',
        //     'value' => 'opportunity',
        //     'required' => true,
        // ],
        [
            'ref' => '#form/fields[]',
            'name' => 'priority',
            'label' => __('Lead priority', 'forms-bridge'),
            'type' => 'number',
            'min' => 0,
            'max' => 3,
            'required' => true,
        ],
        // [
        //     'ref' => '#form/fields[]',
        //     'name' => 'team_type',
        //     'label' => __('Team type', 'forms-bridge'),
        //     'type' => 'string',
        //     'value' => 'sales',
        //     'required' => true,
        // ],
    ],
    'bridge' => [
        'model' => 'crm.lead',
        'pipes' => [
            [
                'from' => 'submission_id',
                'to' => 'submission_id',
                'cast' => 'null',
            ],
            [
                'from' => 'priority',
                'to' => 'priority',
                'cast' => 'string',
            ],
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'name',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'user_email',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'priority',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'type',
                'type' => 'hidden',
                'value' => 'opportunity',
                'required' => true,
            ],
            [
                'name' => 'team_type',
                'type' => 'hidden',
                'value' => 'sales',
                'required' => true,
            ],
            [
                'label' => __('Your email', 'forms-bridge'),
                'name' => 'email_from',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Your phone', 'forms-bridge'),
                'name' => 'phone',
                'type' => 'text',
            ],
            [
                'label' => __('Your mobile', 'forms-bridge'),
                'name' => 'mobile',
                'type' => 'text',
            ],
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'street',
                'type' => 'text',
            ],
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
            ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip',
                'type' => 'text',
            ],
        ],
    ],
];
