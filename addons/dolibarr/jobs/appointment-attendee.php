<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_appointment_attendee($payload, $bridge)
{
    $contact = forms_bridge_dolibarr_create_contact($payload, $bridge);

    if (is_wp_error($contact)) {
        return $contact;
    }

    $payload['socpeopleassigned'][$contact['id']] = [
        'id' => $contact['id'],
        'mandatory' => 0,
        'answer_status' => 0,
        'transparency' => 0,
    ];

    return $payload;
}

return [
    'title' => __('Appointment attendee', 'forms-bridge'),
    'description' => __(
        'Create a contact and binds it to the appointment as an attendee',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_appointment_attendee',
    'input' => [
        [
            'name' => 'lastname',
            'required' => true,
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'firstname',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'civility_code',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'status',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'note_public',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'note_private',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'address',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'zip',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'town',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'country_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'state_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'region_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'phone_pro',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'phone_perso',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'phone_mobile',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'fax',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'url',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'socid',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'poste',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'stcomm_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'no_email',
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
