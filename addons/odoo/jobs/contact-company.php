<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_contact_company($payload, $bridge)
{
    $company = forms_bridge_odoo_create_company($payload, $bridge);

    if (is_wp_error($company)) {
        return $company;
    }

    $payload['parent_id'] = $company['id'];
    return $payload;
}

return [
    'title' => __('Contact\'s company', 'forms-bridge'),
    'description' => __(
        'Creates a company and sets its ID as the parent_id of the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_contact_company',
    'input' => [
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
            'required' => true,
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
    ],
    'output' => [
        [
            'name' => 'parent_id',
            'schema' => ['type' => 'integer'],
        ],
    ],
];
