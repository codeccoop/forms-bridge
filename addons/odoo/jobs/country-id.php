<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_country_id_from_code($payload, $bridge)
{
    global $forms_bridge_iso2_countries;

    if (!isset($forms_bridge_iso2_countries[$payload['country_code']])) {
        return $payload;
    }

    $response = $bridge
        ->patch([
            'endpoint' => 'res.country',
            'method' => 'search',
        ])
        ->submit([['code', '=', $payload['country_code']]]);

    if (is_wp_error($response)) {
        return $response;
    }

    $payload['country_id'] = $response['data']['result'][0];
    return $payload;
}

return [
    'title' => __('Country ID from code', 'forms-bridge'),
    'description' => __(
        'Given a iso2 code code gets the internal country ID',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_country_id_from_code',
    'input' => [
        [
            'name' => 'country_code',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'country_code',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'country_id',
            'schema' => ['type' => 'integer'],
        ],
    ],
];
