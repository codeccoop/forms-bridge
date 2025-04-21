<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_skip_contact($payload, $bridge)
{
    $contact = forms_bridge_dolibarr_search_contact($payload, $bridge);

    if (is_wp_error($contact)) {
        return $contact;
    }

    if (isset($contact['id'])) {
        $patch = $payload;
        $patch['id'] = $contact['id'];

        $response = forms_bridge_dolibarr_update_contact($patch, $bridge);

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
        'Aborts form submission if the contact exists',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_skip_contact',
    'input' => [
        [
            'name' => 'email',
            'required' => true,
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'firstname',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'lastname',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'socid',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
            'forward' => true,
        ],
        [
            'name' => 'firstname',
            'schema' => ['type' => 'string'],
            'forward' => true,
        ],
        [
            'name' => 'lastname',
            'schema' => ['type' => 'string'],
            'forward' => true,
        ],
        [
            'name' => 'socid',
            'schema' => ['type' => 'string'],
            'forward' => true,
        ],
    ],
];
