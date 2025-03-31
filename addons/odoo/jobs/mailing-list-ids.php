<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_mailing_list_ids($payload)
{
    $list_ids = is_string($payload['list_ids'])
        ? explode(',', $payload['list_ids'])
        : (array) $payload['list_ids'];

    $payload['list_ids'] = array_map('intval', $list_ids);

    return $payload;
}

return [
    'title' => __('Mailing list IDs', 'forms-bridge'),
    'description' => __(
        'Split comma separated into an array of integer IDs',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_mailing_list_ids',
    'input' => [
        [
            'name' => 'list_ids',
            'type' => 'string',
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'list_ids',
            'type' => 'array',
            'items' => ['type' => 'integer'],
            'additionalItems' => true,
        ],
    ],
];
