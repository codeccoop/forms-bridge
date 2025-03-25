<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_appointment_attendee($payload, $bridge)
{
    $contact = forms_bridge_dolibarr_search_contact($payload, $bridge);

    if (is_wp_error($contact)) {
        return $contact;
    }

    if (isset($contact['id'])) {
        $payload['socpeopleassigned'][$contact['id']] = [
            'id' => $contact['id'],
            'mandatory' => 0,
            'answer_status' => 0,
            'transparency' => 0,
        ];

        return $payload;
    }

    $backend = $bridge->backend;
    $dolapykey = $bridge->api_key->key;

    $contact = [];
    $contact_fields = ['email', 'firstname', 'lastname', 'socid', 'poste'];

    foreach ($contact_fields as $field) {
        if (isset($payload[$field])) {
            $contact[$field] = $payload[$field];
        }
    }

    if (empty($contact)) {
        return $payload;
    }

    $response = $backend->post('/api/index.php/contacts', $contact, [
        'DOLAPIKEY' => $dolapykey,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $payload['socpeopleassigned'][$response['body']] = [
        'id' => $response['body'],
        'mandatory' => 0,
        'answer_status' => 0,
        'transparency' => 0,
    ];

    return $payload;
}

return [
    'title' => __('Appointment attendee', 'forms-bridge'),
    'description' => __(
        'Adds a contact ID as an appointment attendee',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_appointment_attendee',
    'input' => [
        [
            'name' => 'email',
            'type' => 'string',
        ],
        [
            'name' => 'firstname',
            'type' => 'string',
        ],
        [
            'name' => 'lastname',
            'type' => 'string',
        ],
        [
            'name' => 'socid',
            'type' => 'string',
        ],
        [
            'name' => 'poste',
            'type' => 'string',
        ],
    ],
    'output' => [
        [
            'name' => 'socpeopleassigned',
            'type' => 'object',
            'properties' => [
                'socid' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string'],
                        'mandatory' => ['type' => 'string'],
                        'answer_status' => ['type' => 'string'],
                        'transparency' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ],
];
