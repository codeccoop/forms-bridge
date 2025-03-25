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
        : 'pending';

    return $payload;
}

return [
    'title' => __('MailChimp contact status', 'forms-bridge'),
    'description' => __('Validates contact status value', 'forms-bridge'),
    'method' => 'forms_bridge_mailchimp_contact_status',
    'input' => [
        [
            'name' => 'status',
            'type' => 'string',
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'status',
            'type' => 'string',
        ],
    ],
];
