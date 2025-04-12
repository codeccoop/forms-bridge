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

    $contact = [
        'email' => $payload['email'],
        'firstname' => $payload['firstname'],
        'lastname' => $payload['lastname'],
    ];

    $contact_fields = ['socid', 'poste'];
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
            'required' => true,
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'firstname',
            'required' => true,
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'lastname',
            'required' => true,
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'socid',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'poste',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'socpeopleassigned',
            'schema' => [
                'type' => 'array',
                'items' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'mandatory' => ['type' => 'string'],
                            'answer_status' => ['type' => 'string'],
                            'transparency' => ['type' => 'string'],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ],
    ],
];
