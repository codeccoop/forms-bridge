<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_appointment_attendees($payload, $bridge)
{
    $partner = forms_bridge_odoo_create_partner($payload, $bridge);

    if (is_wp_error($partner)) {
        return $partner;
    }

    $payload['partner_ids'][] = $partner['id'];

    if (isset($payload['user_id'])) {
        $user_response = $bridge
            ->patch([
                'name' => 'odoo-get-user-by-id',
                'endpoint' => 'res.users',
                'method' => 'read',
            ])
            ->submit([$payload['user_id']]);

        if (is_wp_error($user_response)) {
            return $user_response;
        }

        $payload['partner_ids'][] =
            $user_response['data']['result'][0]['partner_id'][0];
    }

    return $payload;
}

return [
    'title' => __('Appointment attendees', 'forms-bridge'),
    'description' => __(
        'Search for partner by email or creates a new one and sets it as the appointment attendee. If user_id, also adds user as attendee.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_appointment_attendees',
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
            'name' => 'user_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'partner_ids',
            'schema' => [
                'type' => 'array',
                'items' => ['type' => 'integer'],
                'additionalItems' => true,
            ],
        ],
    ],
];
