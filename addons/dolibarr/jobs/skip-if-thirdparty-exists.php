<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_skip_thirdparty($payload, $bridge)
{
    $thirdparty = forms_bridge_dolibarr_search_thirdparty($payload, $bridge);

    if (is_wp_error($thirdparty)) {
        return $thirdparty;
    }

    if ($thirdparty) {
        return;
    }

    return $payload;
}

return [
    'title' => __('Skip if thirdparty exists', 'forms-bridge'),
    'description' => __(
        'Aborts form submission if a contact with same email exists.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_skip_thirdparty',
    'input' => [
        [
            'name' => 'email',
            'type' => 'string',
        ],
        [
            'name' => 'name',
            'type' => 'string',
        ],
        [
            'name' => 'idprof1',
            'type' => 'string',
        ],
    ],
    'output' => [
        [
            'name' => 'email',
            'type' => 'string',
        ],
        [
            'name' => 'name',
            'type' => 'string',
        ],
        [
            'name' => 'idprof1',
            'type' => 'string',
        ],
    ],
];
