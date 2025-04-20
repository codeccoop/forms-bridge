<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_brige_odoo_update_mailing_contact($payload, $bridge)
{
    $response = $bridge
        ->patch([
            'name' => 'odoo-search-mailing-contact-by-email',
            'template' => null,
            'method' => 'search',
            'endpoint' => 'mailing.contact',
        ])
        ->submit([['email', '=', $payload['email']]]);

    if (!is_wp_error($response)) {
        $contact_id = $response['data']['result'][0];
        $list_ids = $payload['list_ids'];

        $response = $bridge
            ->patch([
                'name' => 'odoo-update-mailing-contact-subscriptions',
                'template' => null,
                'endpoint' => 'mailing.contact',
                'method' => 'write',
            ])
            ->submit([$contact_id], ['list_ids' => $list_ids]);

        if (is_wp_error($response)) {
            return $response;
        }

        return;
    }

    return $payload;
}

return [
    'title' => __('Skip subscription', 'forms-bridge'),
    'description' => __(
        'Search for a subscribed mailing contact, updates its subscriptions and skips if succeed',
        'forms-bridge'
    ),
    'method' => 'forms_brige_odoo_update_mailing_contact',
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
    ],
    'output' => [
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
        ],
    ],
];
