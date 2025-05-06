<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Skip subscription', 'forms-bridge'),
    'description' => __(
        'Skip subscription if the mailchimp field is not true',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_mailchimp_skip_subscription',
    'input' => [
        [
            'name' => 'mailchimp',
            'schema' => ['type' => 'boolean'],
            'required' => true,
        ],
    ],
    'output' => [],
];

function forms_bridge_mailchimp_skip_subscription($payload)
{
    if ($payload['mailchimp'] === true) {
        return $payload;
    }
}
