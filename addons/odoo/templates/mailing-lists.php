<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Mailing Lists', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Mailing Lists', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'list_ids',
            'label' => __('List IDs', 'forms-bridge'),
            'description' => __('List IDs separated by commas', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
    ],
    'bridge' => [
        'model' => 'mailing.contact',
        'workflow' => ['odoo-mailing-list-ids', 'odoo-mailing-contact'],
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
            ],
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'list_ids',
                'type' => 'hidden',
                'required' => true,
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
        ],
    ],
];
