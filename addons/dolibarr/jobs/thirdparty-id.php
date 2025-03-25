<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_thirdparty_id($payload, $bridge)
{
    $query = [
        'name' => $payload['name'],
        'email' => $payload['email'],
    ];

    $thirdparty = forms_bridge_dolibarr_search_thirdparty($query, $bridge);

    if (is_wp_error($thirdparty)) {
        return $thirdparty;
    }

    if (isset($thirdparty['id'])) {
        $payload['socid'] = $thirdparty['id'];
        return $payload;
    }

    $backend = $bridge->backend;
    $dolapikey = $bridge->api_key->key;

    $payload = forms_bridge_dolibarr_next_code_client($payload, $bridge);
    if (is_wp_error($payload)) {
        return $payload;
    }

    $thirdparty = [
        'code_client' => $payload['code_client'],
        'email' => $payload['email'],
        'name' => $payload['name'],
        'status' => $payload['status'] ?? '1',
        'typent_id' => $payload['typent_id'] ?? '4',
        'client' => $payload['client'] ?? '2',
        'stcomm_id' => $payload['stcomm_id'] ?? '0',
    ];

    $thirdparty_fields = [
        'idprof1',
        'address',
        'zip',
        'town',
        'country_id',
        'note_public',
    ];

    foreach ($thirdparty_fields as $field) {
        if (isset($payload[$field])) {
            $thirdparty[$field] = $payload[$field];
        }
    }

    $response = $backend->post('/api/index.php/thirdparties', $thirdparty, [
        'DOLAPIKEY' => $dolapikey,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $payload['socid'] = $response['body'];
    unset($payload['code_client']);

    return $payload;
}

return [
    'title' => __('Thirdparty ID', 'forms-bridge'),
    'description' => __(
        'Gets the ID of a third party or creates a new thirparty if it doesn\'t exists',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_thirdparty_id',
    'input' => [
        [
            'name' => 'name',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'email',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'typent_id',
            'type' => 'string',
        ],
        [
            'name' => 'status',
            'type' => 'string',
        ],
        [
            'name' => 'client',
            'type' => 'string',
        ],
        [
            'name' => 'stcomm_id',
            'type' => 'string',
        ],
        [
            'name' => 'address',
            'type' => 'string',
        ],
        [
            'name' => 'zip',
            'type' => 'string',
        ],
        [
            'name' => 'town',
            'type' => 'string',
        ],
        [
            'name' => 'state',
            'type' => 'string',
        ],
        [
            'name' => 'country_id',
            'type' => 'string',
        ],
        [
            'name' => 'idprof1',
            'type' => 'string',
        ],
        [
            'name' => 'note_public',
            'type' => 'string',
        ],
    ],
    'output' => [
        [
            'name' => 'socid',
            'type' => 'string',
        ],
    ],
];
