<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Skip subscription', 'forms-bridge'),
    'description' => __(
        'Skip subscription if the listmonk field is not true',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_listmonk_skip_subscription',
    'input' => [
        [
            'name' => 'listmonk',
            'schema' => ['type' => 'boolean'],
            'required' => true,
        ],
    ],
    'output' => [],
];

function forms_bridge_listmonk_skip_subscription($payload)
{
    if ($payload['listmonk'] === true) {
        return $payload;
    }
}
