<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_bigin_appointment_participant($payload, $bridge)
{
    $payload = forms_bridge_zoho_bigin_contact_name($payload, $bridge);

    if (is_wp_error($payload)) {
        return $payload;
    }

    $payload['Participants'] = $payload['Participants'] ?? [];
    $payload['Participants'][] = [
        'type' => 'contact',
        'participant' => $payload['Contact_Name']['id'],
    ];

    unset($payload['Contact_Name']);

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
