<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Skip subscription', 'forms-bridge'),
    'description' => __(
        'Skip subscription if the brevo field is not true',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_skip_subscription',
    'input' => [
        [
            'name' => 'brevo',
            'schema' => ['type' => 'boolean'],
            'required' => true,
        ],
    ],
    'output' => [],
];

function forms_bridge_brevo_skip_subscription($payload)
{
    if ($payload['brevo'] === true) {
        return $payload;
    }
}
