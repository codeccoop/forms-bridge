<?php

function forms_bridge_mailchimp_merge_fields($payload)
{
    $payload['merge_fields'] = $payload['merge_fields'] ?? [];

    $known_fields = [
        'email_address',
        'status',
        'email_type',
        'language',
        'location',
        'vip',
        'tags',
        'merge_fields',
    ];

    foreach ($payload as $field => $value) {
        if (!in_array($field, $known_fields)) {
            $payload['merge_fields'][strtoupper($field)] = $value;
            unset($payload[$field]);
        }
    }

    return $payload;
}

return [
    'title' => __('MailChimp merge fields', 'forms-bridge'),
    'description' => __(
        'Puts all non mailchimp subscription standard fields into an associative array named "merge_fields"',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_mailchimp_merge_fields',
    'input' => [
        [
            'name' => 'email_address',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'status',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'email_type',
            'type' => 'string',
        ],
        [
            'name' => 'location',
            'type' => 'object',
        ],
        [
            'name' => 'language',
            'type' => 'string',
        ],
        [
            'name' => 'vip',
            'type' => 'string',
        ],
        [
            'name' => 'tags',
            'type' => 'array',
        ],
    ],
    'output' => [
        [
            'name' => 'email_address',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'status',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'email_type',
            'type' => 'string',
        ],
        [
            'name' => 'location',
            'type' => 'object',
        ],
        [
            'name' => 'language',
            'type' => 'string',
        ],
        [
            'name' => 'vip',
            'type' => 'string',
        ],
        [
            'name' => 'tags',
            'type' => 'array',
        ],
        [
            'name' => 'merge_fields',
            'type' => 'object',
        ],
    ],
];
