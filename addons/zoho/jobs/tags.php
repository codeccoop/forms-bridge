<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_zoho_tags($payload)
{
    $tags = is_string($payload['Tag'])
        ? explode(',', $payload['Tag'])
        : (array) $payload['Tag'];

    $tags = array_map('trim', array_map('strval', $tags));

    if (!empty($tags)) {
        $payload['Tag'] = [];
        foreach ($tags as $tag) {
            $payload['Tag'][] = ['name' => $tag];
        }
    }

    return $payload;
}

return [
    'title' => __('Zoho tags', 'forms-bridge'),
    'description' => __(
        'Split comma separated tags and format as a collection of objects',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_zoho_tags',
    'input' => [
        [
            'name' => 'Tag',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'Tag',
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
                'additionalItems' => true,
            ],
            'touch' => true,
        ],
    ],
];
