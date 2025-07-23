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

    if (isset($thirdparty['id'])) {
        $patch = $payload;
        $patch['id'] = $thirdparty['id'];

        if (isset($thirdparty['code_client'])) {
            $patch['code_client'] = $thirdparty['code_client'];
        }

        $response = forms_bridge_dolibarr_update_thirdparty($patch, $bridge);

        if (is_wp_error($response)) {
            return $response;
        }

        return;
    }

    return $payload;
}

return [
    'title' => __('Skip if thirdparty exists', 'forms-bridge'),
    'description' => __(
        'Aborts form submission if a thirdparty already exists',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_skip_thirdparty',
    'input' => [
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'idprof1',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
            'requires' => ['name'],
        ],
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
            'requires' => ['email'],
        ],
        [
            'name' => 'idprof1',
            'schema' => ['type' => 'string'],
            'requires' => ['idprof1'],
        ],
    ],
];
