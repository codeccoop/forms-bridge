<?php

function forms_bridge_mailchimp_contact_status($payload)
{
    $payload['status'] = in_array($payload['status'], [
        'subscribed',
        'unsubscribed',
        'cleaned',
        'pending',
        'transactional',
    ])
        ? $payload['status']
        : 'subscribed';

    return $payload;
}

return [
    'title' => __('MailChimp contact status', 'forms-bridge'),
    'description' => __(
        'Validates contact status value or sets subscribed as its default value',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_mailchimp_contact_status',
    'input' => [
        [
            'name' => 'status',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'status',
            'schema' => ['type' => 'string'],
            'touch' => true,
        ],
    ],
];
