<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_contact_ids($payload, $bridge)
{
    $contact = forms_bridge_dolibarr_create_contact($payload, $bridge);

    if (is_wp_error($contact)) {
        return $contact;
    }

    $payload['contact_ids'][] = (int) $contact['id'];
    return $payload;
}

return [
    'title' => __('Contact', 'forms-bridge'),
    'description' => __(
        'Creates a contact and adds its ID to the contact_ids field of the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_contact_ids',
    'input' => [
        [
            'name' => 'lastname',
            'schema' => ['type' => 'string'],
            'required' => true,
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
            'name' => 'socid',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'poste',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'status',
            'schema' => ['type' => 'string'],
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
            'name' => 'state_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'region_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'url',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'no_email',
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
            'name' => 'stcomm_id',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'default_lang',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'contact_ids',
            'schema' => [
                'type' => 'array',
                'items' => ['type' => 'integer'],
                'additionalItems' => true,
            ],
        ],
    ],
];
