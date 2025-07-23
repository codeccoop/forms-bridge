<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_country_id_from_code($payload, $bridge)
{
    global $forms_bridge_iso2_countries;

    if (!isset($forms_bridge_iso2_countries[$payload['country']])) {
        if (!isset($forms_bridge_iso2_countries[$payload['country_id']])) {
            return new WP_Error('Invalid ISO-2 country code', 'forms-bridge');
        }

        // backward compatibility
        $payload['country'] = $payload['country_id'];
    }

    $response = $bridge
        ->patch([
            'name' => 'dolibarr-get-country-id',
            'method' => 'GET',
            'endpoint' =>
                '/api/index.php/setup/dictionary/countries/byCode/' .
                $payload['country'],
        ])
        ->submit();

    if (is_wp_error($response)) {
        return $response;
    }

    $payload['country_id'] = $response['data']['id'];
    return $payload;
}

return [
    'title' => __('Country ID', 'forms-bridge'),
    'description' => __(
        'Gets country_id value from country code and replace it on the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_country_id_from_code',
    'input' => [
        [
            'name' => 'country',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'country_id',
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
