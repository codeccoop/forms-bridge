<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_individual_thirdparty_name($payload)
{
    $payload['name'] = "{$payload['firstname']} {$payload['lastname']}";
    return $payload;
}

return [
    'title' => __('Individual thirdparty name', 'forms-bridge'),
    'description' => __(
        'Concatenate first and last name into name',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_individual_thirdparty_name',
    'input' => [
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
    ],
    'output' => [
        [
            'name' => 'name',
            'type' => 'string',
        ],
    ],
];
