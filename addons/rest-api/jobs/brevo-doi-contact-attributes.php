<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_brevo_doi_contact_attributes($payload)
{
    $payload['attributes'] = $payload['attributes'] ?? [];

    $schema = [
        'email',
        'includeListIds',
        'excludeListIds',
        'templateId',
        'redirectionUrl',
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
    'title' => __('Brevo DOI contact attributes', 'forms-bridge'),
    'description' => __(
        'Place all non well known fields as uppercased fields of the attributes object',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_doi_contact_attributes',
    'input' => [
        [
            'name' => 'email',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'includeListIds',
            'type' => 'string',
        ],
        [
            'name' => 'excludeListIds',
            'type' => 'string',
        ],
        [
            'name' => 'templateId',
            'type' => 'string',
        ],
        [
            'name' => 'redirectionUrl',
            'type' => 'string',
        ],
    ],
    'output' => [
        [
            'name' => 'email',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'includeListIds',
            'type' => 'string',
        ],
        [
            'name' => 'excludeListIds',
            'type' => 'string',
        ],
        [
            'name' => 'templateId',
            'type' => 'string',
        ],
        [
            'name' => 'redirectionUrl',
            'type' => 'string',
        ],
        [
            'name' => 'attributes',
            'type' => 'object',
        ],
    ],
];
