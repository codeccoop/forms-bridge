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
        'Aborts form submission if a thirdparty with same idprof1 exists',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_skip_thirdparty',
    'input' => [
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'idprof1',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'idprof1',
            'schema' => ['type' => 'string'],
        ],
    ],
];
