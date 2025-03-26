<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_bigin_appointment_participant($payload, $bridge)
{
    $contact_id = forms_bridge_zoho_bigin_contact_id($payload, $bridge);

    if (is_wp_error($contact_id)) {
        return $contact_id;
    }

    $payload['Participants'] = $payload['Participants'] ?? [];
    $payload['Participants'][] = [
        'type' => 'contact',
        'participant' => $contact_id,
    ];

    return $payload;
}

return [
    'title' => __('Bigin appointment participant', 'forms-bridge'),
    'description' => __(
        'Search for a contact or creates a new one and sets its ID as appointment participant',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_bigin_appointment_participant',
    'input' => [
        [
            'name' => 'Email',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'First_Name',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'Last_Name',
            'type' => 'string',
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'Participants',
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'type' => ['type' => 'string'],
                    'participant' => ['type' => 'string'],
                ],
            ],
        ],
    ],
];
