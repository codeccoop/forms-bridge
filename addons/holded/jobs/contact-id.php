<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_holded_contact_id($payload, $bridge)
{
    $contact = forms_bridge_holded_create_contact($payload, $bridge);

    if (is_wp_error($contact)) {
        return $contact;
    }

    $payload['contactId'] = $contact['id'];
    return $payload;
}

return [
    'title' => __('Contact ID', 'forms-bridge'),
    'description' => __(
        'Creates a new contact and sets its ID as the contactId field of the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_holded_contact_id',
    'input' => [
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'CustomId',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'tradeName',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'mobile',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'phone',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'type',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'code',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'vatnumber',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'iban',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'swift',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'billAddress',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'address' => ['type' => 'string'],
                    'postalCode' => ['type' => 'string'],
                    'city' => ['type' => 'string'],
                    'countryCode' => ['type' => 'string'],
                ],
                'additionalProperties' => true,
            ],
        ],
        [
            'name' => 'defaults',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'language' => ['type' => 'string'],
                ],
                'additionalProperties' => true,
            ],
        ],
        [
            'name' => 'tags',
            'schema' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'additionalItems' => true,
            ],
        ],
        [
            'name' => 'note',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'isperson',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'contactPersons',
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                        'phone' => ['type' => 'string'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
        ],
        [
            'name' => 'shippingAddresses',
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'address' => ['type' => 'string'],
                        'city' => ['type' => 'string'],
                        'postalCode' => ['type' => 'string'],
                        'province' => ['type' => 'string'],
                        'country' => ['type' => 'string'],
                        'note' => ['type' => 'string'],
                        'privateNote' => ['type' => 'string'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
        ],
    ],
    'output' => [
        [
            'name' => 'contactId',
            'schema' => ['type' => 'string'],
        ],
    ],
];
