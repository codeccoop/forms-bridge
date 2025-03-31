<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_appointment_owner_by_email($payload, $bridge)
{
    $query = ['email' => $payload['owner_email']];
    $user = forms_bridge_odoo_search_user_by_email($query, $bridge);

    if (is_wp_error($user)) {
        return $user;
    }

    $payload['partner_ids'] = (array) ($payload['partner_ids'] ?? []);
    $payload['partner_ids'][] = $user['commercial_partner_id'][0];
    return $payload;
}

return [
    'title' => __('Appointment owner', 'forms-bridge'),
    'description' => __(
        'Search for user by email and sets it as the appointment ower',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_appointment_owner_by_email',
    'input' => [
        [
            'name' => 'owner_email',
            'type' => 'string',
            'required' => true,
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
