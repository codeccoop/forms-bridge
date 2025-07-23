<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_country_id_from_code($payload, $bridge)
{
    global $forms_bridge_iso2_countries;

    if (!isset($forms_bridge_iso2_countries[$payload['country']])) {
        if (!isset($forms_bridge_iso2_countries[$payload['country_code']])) {
            return new WP_Error('Invalid ISO-2 country code', 'forms-bridge');
        }

        // backward compatibility
        $payload['country'] = $payload['country_code'];
    }

    $response = $bridge
        ->patch([
            'name' => 'odoo-get-country-id',
            'endpoint' => 'res.country',
            'method' => 'search',
        ])
        ->submit([['code', '=', $payload['country']]]);

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
            'name' => 'country',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'country_code',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'country_id',
            'schema' => ['type' => 'integer'],
        ],
    ],
];
