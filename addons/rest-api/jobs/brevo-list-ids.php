<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_brevo_list_ids($payload)
{
    foreach (['listIds', 'includeListIds'] as $field) {
        if (!isset($payload[$field])) {
            continue;
        }

        $list = is_string($payload[$field])
            ? explode(',', $payload[$field])
            : (array) $payload[$field];

        $payload[$field] = array_filter(array_map('intval', $list));
    }

    return $payload;
}

return [
    'title' => __('Brevo list IDs', 'forms-bridge'),
    'description' => __(
        'Formats the submission payload listIds field as an array of integers.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_list_ids',
    'input' => [
        [
            'name' => 'listIds',
            'type' => 'string',
        ],
        [
            'name' => 'includeListIds',
            'type' => 'string',
        ],
    ],
    'output' => [
        [
            'name' => 'listIds',
            'type' => 'string',
        ],
        [
            'name' => 'includeListIds',
            'type' => 'string',
        ],
    ],
];
