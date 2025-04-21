<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'dolibarr-contacts') {
            $index = array_search(
                'no_email',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['bridge']['custom_fields'][$index];
                $field['value'] = $field['value'] ? '0' : '1';
            }
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Contacts', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/index.php/contacts',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'no_email',
            'label' => __('Subscrive to email', 'forms-bridge'),
            'type' => 'boolean',
            'default' => true,
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Contacts', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Contacts', 'forms-bridge'),
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
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/contacts',
        'custom_fields' => [
            [
                'name' => 'status',
                'value' => '1',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'no_email',
                    'to' => 'no_email',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => ['dolibarr-skip-if-contact-exists'],
    ],
];
