<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'odoo-contacts') {
            return $payload;
        }

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-contact-by-email',
                'template' => null,
                'method' => 'search',
            ])
            ->submit([
                ['email', '=', $payload['email']],
                ['is_company', '=', false],
            ]);

        if (!is_wp_error($response)) {
            return;
        }

        return $payload;
    },
    10,
    2
);

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
        'mappers' => [
            [
                'from' => 'is_company',
                'to' => 'is_company',
                'cast' => 'boolean',
            ],
        ],
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
