<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Contacts', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Contacts', 'forms-bridge'),
        ],
    ],
    'bridge' => [
        'model' => 'res.partner',
        'mutations' => [
            [
                [
                    'from' => 'is_company',
                    'to' => 'is_company',
                    'cast' => 'boolean',
                ],
            ],
        ],
        'workflow' => ['odoo-skip-if-contact-exists'],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'is_company',
                'type' => 'hidden',
                'value' => '0',
            ],
            [
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'name',
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
