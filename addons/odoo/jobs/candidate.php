<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Candidate', 'forms-bridge'),
    'description' => __(
        'Creates a recruitement candidate and sets its ID as the candidate_id field of the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_create_candidate',
    'input' => [
        [
            'name' => 'partner_name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'partner_phone',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'email_from',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'user_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'type_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'partner_id',
            'schema' => ['type' => 'integer'],
        ],
    ],
    'output' => [
        [
            'name' => 'candidate_id',
            'schema' => ['type' => 'integer'],
        ],
    ],
];

function forms_bridge_odoo_create_candidate($payload, $bridge)
{
    $candidate = [
        'partner_name' => $payload['partner_name'],
    ];

    $fields = [
        'partner_id',
        'partner_phone',
        'email_from',
        'user_id',
        'type_id',
    ];

    foreach ($fields as $field) {
        if (isset($payload[$field])) {
            $candidate[$field] = $payload[$field];
        }
    }

    $query = [['partner_name', '=', $candidate['partner_name']]];

    if (isset($candidate['email_from'])) {
        $query[] = ['email_from', '=', $candidate['email_from']];
    }

    $response = $bridge
        ->patch([
            'name' => '__odoo-search-candidate',
            'endpoint' => 'hr.candidate',
            'method' => 'search',
        ])
        ->submit($query);

    if (!is_wp_error($response)) {
        $payload['candidate_id'] = (int) $response['data']['result'][0];
        return $payload;
    }

    $response = $bridge
        ->patch([
            'name' => '__odoo-create-candidate',
            'endpoint' => 'hr.candidate',
        ])
        ->submit($candidate);

    if (is_wp_error($response)) {
        return $response;
    }

    $payload['candidate_id'] = (int) $response['data']['result'];

    return $payload;
}
