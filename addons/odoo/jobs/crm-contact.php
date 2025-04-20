<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_crm_lead_contact($payload, $bridge)
{
    $partner = forms_bridge_odoo_create_partner($payload, $bridge);

    if (is_wp_error($partner)) {
        return $partner;
    }

    $payload['email_from'] = $partner['email'];

    if (!empty($partner['parent_id'][0])) {
        $payload['partner_id'] = $partner['parent_id'][0];
    } else {
        $payload['partner_id'] = $partner['id'];
    }

    return $payload;
}

return [
    'title' => __('CRM lead contact', 'forms-bridge'),
    'description' => __(
        'Creates a new contact and sets its email as the email_from value on the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_crm_lead_contact',
    'input' => [
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
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
            'name' => 'employee',
            'schema' => ['type' => 'boolean'],
        ],
        [
            'name' => 'function',
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
            'name' => 'zip',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'city',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'country_code',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'parent_id',
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
            'name' => 'email_from',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'partner_id',
            'schema' => ['type' => 'integer'],
        ],
    ],
];
