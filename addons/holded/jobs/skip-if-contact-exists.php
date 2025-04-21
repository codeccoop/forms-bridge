<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_holded_skip_contact($payload, $bridge)
{
    $contact = forms_bridge_holded_search_contact($payload, $bridge);

    if (is_wp_error($contact)) {
        return $contact;
    }

    if ($contact) {
        $payload['id'] = $contact['id'];
        $contact = forms_bridge_holded_update_contact($payload, $bridge);

        if (is_wp_error($contact)) {
            return $contact;
        }

        return;
    }

    return $payload;
}

return [
    'title' => __('Skip if contact exists', 'forms-bridge'),
    'description' => __(
        'Search for a contact and skip submission if it exists',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_holded_skip_contact',
    'input' => [
        [
            'name' => 'phone',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'mobile',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'customId',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'phone',
            'schema' => ['type' => 'string'],
            'forward' => true,
        ],
        [
            'name' => 'mobile',
            'schema' => ['type' => 'string'],
            'forward' => true,
        ],
        [
            'name' => 'customId',
            'schema' => ['type' => 'string'],
            'forward' => true,
        ],
    ],
];
