<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_bigin_appointment_participant($payload, $bridge)
{
    $contact = forms_bridge_bigin_create_contact($payload, $bridge);

    if (is_wp_error($payload)) {
        return $payload;
    }

    $payload['Participants'][] = [
        'type' => 'contact',
        'participant' => $contact['id'],
    ];

    return $payload;
}

return [
    'title' => __('Appointment participant', 'forms-bridge'),
    'description' => __(
        'Search for a contact or creates a new one and sets its ID as appointment participant',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_bigin_appointment_participant',
    'input' => [
        [
            'name' => 'Last_Name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'First_Name',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Full_Name',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Phone',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Mobile',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Mailing_Street',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Mailing_City',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Mailing_Zip',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Mailing_State',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Mailing_Country',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Description',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Account_Name',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Title',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'Participants',
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'participant' => ['type' => 'string'],
                    ],
                ],
                'additionalItems' => true,
            ],
        ],
    ],
];
