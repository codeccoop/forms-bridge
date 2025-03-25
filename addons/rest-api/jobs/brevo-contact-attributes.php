<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_brevo_contact_attributes($payload)
{
    $payload['attributes'] = $payload['attributes'] ?? [];

    $schema = [
        'email',
        'listIds',
        'updateEnabled',
        'emailBlacklisted',
        'smsBalcklisted',
        'attributes',
    ];

    foreach ($payload as $field => $value) {
        if (!in_array($field, $schema)) {
            $payload['attributes'][strtoupper($field)] = $value;
            unset($payload[$field]);
        }
    }

    return $payload;
}

return [
    'title' => __('Brevo contact attributes', 'forms-bridge'),
    'description' => __(
        'Place all non well known fields as uppercased fields of the attributes object',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_contact_attributes',
    'input' => [
        [
            'name' => 'email',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'listIds',
            'type' => 'string',
        ],
        [
            'name' => 'updateEnabled',
            'type' => 'boolean',
        ],
        [
            'name' => 'emailBlacklisted',
            'type' => 'boolean',
        ],
        [
            'name' => 'smsBalcklisted',
            'type' => 'boolean',
        ],
    ],
    'output' => [
        [
            'name' => 'email',
            'type' => 'string',
        ],
        [
            'name' => 'listIds',
            'type' => 'string',
        ],
        [
            'name' => 'updateEnabled',
            'type' => 'boolean',
        ],
        [
            'name' => 'emailBlacklisted',
            'type' => 'boolean',
        ],
        [
            'name' => 'smsBalcklisted',
            'type' => 'boolean',
        ],
        [
            'name' => 'attributes',
            'type' => 'object',
        ],
    ],
];
