<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template === 'dolibarr-crm') {
            // do something
        }

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('Dolibarr Sale Order', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'label' => __('Form title', 'forms-bridge'),
            'default' => __('Dolibarr Sale Orders', 'forms-bridge'),
            'type' => 'string',
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'product_ref',
            'label' => __('Product Ref', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'product_price',
            'label' => __('Product unit price', 'forms-bridge'),
            'type' => 'number',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'tva_tx',
            'label' => __('Product tax percentage', 'forms-bridge'),
            'type' => 'number',
            'required' => true,
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Endpoint', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => '/api/index.php/orders',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'product_ref',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'product_price',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'tva_tx',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'label' => __('Email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Comments', 'forms-bridge'),
                'name' => 'comments',
                'type' => 'textarea',
                'required' => true,
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/orders',
        'pipes' => [
            [
                'from' => 'submission_id',
                'to' => 'submission_id',
                'cast' => 'null',
            ],
            [
                'from' => 'comments',
                'to' => 'note_private',
                'cast' => 'string',
            ],
        ],
    ],
];
