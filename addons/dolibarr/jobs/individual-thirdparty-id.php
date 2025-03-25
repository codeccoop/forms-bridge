<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_individual_thirdparty_id($payload, $bridge)
{
    $name = "{$payload['firstname']} {$payload['lastname']}";

    $query = [
        'typent_id' => '8',
        'email' => $payload['email'],
        'name' => $name,
    ];

    $result = forms_bridge_dolibarr_search_thirdparty($query, $bridge);

    if (is_wp_error($result)) {
        return $result;
    }

    if (isset($result['id'])) {
        $payload['socid'] = $result['id'];
        return $payload;
    }

    $backend = $bridge->backend;
    $dolapikey = $bridge->api_key->key;

    $payload = forms_bridge_dolibarr_next_code_client($payload, $bridge);
    if (is_wp_error($payload)) {
        return $payload;
    }

    $thirdparty = [
        'typent_id' => '8',
        'code_client' => $payload['code_client'],
        'email' => $payload['email'],
        'name' => $name,
        'status' => $payload['status'] ?? '1',
        'client' => $payload['client'] ?? '2',
        'stcomm_id' => $payload['stcomm_id'] ?? '0',
    ];

    $response = $backend->post('/api/index.php/thirdparties', $thirdparty, [
        'DOLAPIKEY' => $dolapikey,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $payload['socid'] = $response['body'];
    return $payload;
}

return [
    'title' => __('Individual thirdparty ID', 'forms-bridge'),
    'description' => __(
        'Gets the ID of an individual thirdparty or creates a new thirparty if it doesn\'t exists',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_individual_thirdparty_id',
    'input' => [
        [
            'name' => 'email',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'firstname',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'lastname',
            'type' => 'string',
            'required' => true,
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
    ],
    'output' => [
        [
            'name' => 'socid',
            'type' => 'string',
        ],
    ],
];
