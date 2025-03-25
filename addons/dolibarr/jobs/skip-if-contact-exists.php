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

    if ($contact) {
        return;
    }

    return $payload;
}

return [
    'title' => __('Skip if contact exists', 'forms-bridge'),
    'description' => __(
        'Aborts form submission if a contact with same email exists.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_skip_contact',
    'input' => [
        [
            'name' => 'email',
            'type' => 'string',
        ],
        [
            'name' => 'firstname',
            'type' => 'string',
        ],
        [
            'name' => 'lastname',
            'type' => 'string',
        ],
        [
            'name' => 'socid',
            'type' => 'string',
        ],
    ],
    'output' => [
        [
            'name' => 'email',
            'type' => 'string',
        ],
        [
            'name' => 'firstname',
            'type' => 'string',
        ],
        [
            'name' => 'lastname',
            'type' => 'string',
        ],
        [
            'name' => 'socid',
            'type' => 'string',
        ],
    ],
];
