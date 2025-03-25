<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_next_code_client($payload, $bridge)
{
    $code_client = forms_bridge_dolibarr_get_next_code_client(
        $payload,
        $bridge
    );

    if (is_wp_error($code_client)) {
        return $code_client;
    }

    $payload['code_client'] = $code_client;
    return $payload;
}

return [
    'title' => __('Next code client', 'forms-brige'),
    'description' => __(
        'Query for the next valid thirdparty code client',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_next_code_client',
    'input' => [],
    'output' => [
        [
            'name' => 'code_client',
            'type' => 'string',
        ],
    ],
];
