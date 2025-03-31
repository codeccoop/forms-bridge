<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_appointment_attendee($payload, $bridge)
{
    $payload = forms_bridge_odoo_contact_id_by_email($payload, $bridge);

    if (is_wp_error($payload)) {
        return $payload;
    }

    $payload['partner_ids'] = (array) ($payload['partner_ids'] ?? []);
    $payload['partner_ids'][] = $payload['partner_id'];
    unset($payload['partner_id']);

    return $payload;
}

return [
    'title' => __('Appointment attendee', 'forms-bridge'),
    'description' => __(
        'Search for partner by email or creates a new one and sets it as the appointment attendee',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_appointment_attendee',
    'input' => [
        [
            'name' => 'email',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'contact_name',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'phone',
            'type' => 'string',
        ],
        [
            'name' => 'function',
            'type' => 'string',
        ],
        [
            'name' => 'parent_id',
            'type' => 'string',
        ],
    ],
    'output' => [
        [
            'name' => 'partner_ids',
            'type' => 'array',
            'items' => ['type' => 'integer'],
            'additionalItems' => true,
        ],
    ],
];
