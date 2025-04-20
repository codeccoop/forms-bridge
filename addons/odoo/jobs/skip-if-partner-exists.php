<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_skip_if_partner_exists($payload, $bridge)
{
    $query = [['name', '=', $payload['name']]];

    if (isset($payload['email'])) {
        $query[] = ['email', '=', $payload['email']];
    }

    if (isset($payload['vat'])) {
        $query[] = ['vat', '=', $payload['vat']];
    }

    $response = $bridge
        ->patch([
            'name' => 'odoo-search-contact-by-email',
            'method' => 'search',
            'endpoint' => 'res.partner',
        ])
        ->submit($query);

    if (!is_wp_error($response)) {
        $partner_id = $response['data']['result'][0];

        $response = $bridge
            ->patch([
                'name' => 'odoo-update-contact',
                'method' => 'write',
                'endpoint' => 'res.partner',
            ])
            ->submit([$partner_id], $payload);

        if (is_wp_error($response)) {
            return $response;
        }

        return;
    }

    return $payload;
}

return [
    'title' => __('Skip if contact exists', 'forms-bridge'),
    'description' => __(
        'Search contacts by name, email and vat and skip submission if it exists',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_skip_if_partner_exists',
    'input' => [
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'vat',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
            'forward' => true,
        ],
        [
            'name' => 'vat',
            'schema' => ['type' => 'string'],
            'forward' => true,
        ],
    ],
];
