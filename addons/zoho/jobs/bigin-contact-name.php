<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_zoho_bigin_contact_name($payload, $bridge)
{
    $contact = [
        'Email' => $payload['Email'],
        'First_Name' => $payload['First_name'],
        'Last_Name' => $payload['Last_Name'],
    ];

    if (isset($payload['Phone'])) {
        $contact['Phone'] = $payload['Phone'];
    }

    if (isset($payload['Title'])) {
        $contact['Title'] = $payload['Title'];
    }

    if (isset($payload['Account_Name']['id'])) {
        $contact['Account_Name'] = $payload['Account_Name'];
    }

    $response = $bridge
        ->patch([
            'name' => 'zoho-bigin-contact-name',
            'endpoint' => '/bigin/v2/Contacts',
            'template' => null,
        ])
        ->submit($contact);

    if (is_wp_error($response)) {
        $data = json_decode(
            $response->get_error_data()['response']['body'],
            true
        );

        if ($data['data'][0]['code'] !== 'DUPLICATE_DATA') {
            return $response;
        }

        $contact_id = $data['data'][0]['details']['duplicate_record']['id'];
    } else {
        $contact_id = $response['data']['data'][0]['details']['id'];
    }

    $payload['Contact_Name'] = [
        'id' => $contact_id,
    ];

    return $payload;
}

return [
    'title' => __('Bigin contact name', 'forms-bridge'),
    'description' => __(
        'Search for a contact by email or creates a new if it does\'t exists and replace the name by the ID on the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_zoho_bigin_contact_name',
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
        [
            'name' => 'Phone',
            'type' => 'string',
        ],
        [
            'name' => 'Title',
            'type' => 'string',
        ],
        [
            'name' => 'Account_Name',
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string'],
            ],
        ],
    ],
    'output' => [
        [
            'name' => 'Contact_Name',
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string'],
            ],
        ],
        [
            'name' => 'Account_Name',
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string'],
            ],
        ],
    ],
];
