<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_get_owner_by_email($payload, $bridge)
{
    $backend = $bridge->backend;
    $dolapikey = $bridge->api_key->key;

    $response = $backend->get(
        '/api/index.php/users',
        [
            'limit' => '1',
            'sqlfilters' => "(t.email:=:'{$payload['owner_email']}')",
        ],
        ['DOLAPIKEY' => $dolapikey]
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $payload['userownerid'] = $response['data'][0]['id'];
    return $payload;
}

return [
    'title' => __('Owner ID by email', 'forms-bridge'),
    'description' => __(
        'Search for user ID by email and sets it value as the "userownerid" of the payload.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_get_owner_by_email',
    'input' => [
        [
            'name' => 'owner_email',
            'type' => 'string',
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'userownerid',
            'type' => 'integer',
        ],
    ],
];
