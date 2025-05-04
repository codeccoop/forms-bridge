<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_contact_id($payload, $bridge)
{
    $contact = forms_bridge_odoo_create_partner($payload, $bridge);

    if (is_wp_error($contact)) {
        return $contact;
    }

    $payload['partner_id'] = $contact['id'];
    return $payload;
}

return [
    'title' => __('Contact', 'forms-bridge'),
    'description' => __(
        'Creates a contact and set its ID as the partner_id of the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_contact_id',
    'input' => [
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'title',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'lang',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'vat',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'phone',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'mobile',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'website',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'street',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'street2',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'city',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'zip',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'country_code',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'country_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'additional_info',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'is_public',
            'schema' => ['type' => 'boolean'],
        ],
        [
            'name' => 'parent_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'function',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'partner_id',
            'schema' => ['type' => 'integer'],
        ],
    ],
];
