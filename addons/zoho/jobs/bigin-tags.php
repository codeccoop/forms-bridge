<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_zoho_bigin_tags($payload)
{
    $tags = is_string($payload['Tag'])
        ? explode(',', $payload['Tag'])
        : (array) $payload['Tag'];

    $tags = array_map('trim', array_map('strval', $tags));

    $payload['Tag'] = [];
    foreach ($tags as $tag) {
        $payload['Tag'][] = ['name' => $tag];
    }

    return $payload;
}

return [
    'title' => __('Bigin tags', 'forms-bridge'),
    'description' => __(
        'Split comma separated tags and format as a collection of objects',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_zoho_bigin_tags',
    'input' => [
        [
            'name' => 'Tag',
            'type' => 'string',
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'Tag',
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
            ],
        ],
    ],
];
