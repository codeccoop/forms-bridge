<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Campaign ID', 'forms-bridge'),
    'description' => __(
        'Gets the campaign ID from the bridge endpoint and place it on the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_financoop_campaign_id',
    'input' => [],
    'output' => [
        [
            'name' => 'campaign_id',
            'schema' => ['type' => 'integer'],
        ],
    ],
];

function forms_bridge_financoop_campaign_id($payload, $bridge)
{
    if (preg_match('/(?<=campaign\/)\d+/', $bridge->endpoint, $matches)) {
        $campaign_id = (int) $matches[0];
        $payload['campaign_id'] = $campaign_id;
    }

    return $payload;
}
