<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_country_id($payload)
{
    global $forms_bridge_dolibarr_countries;
    if (!isset($forms_bridge_dolibarr_countries[$payload['country_id']])) {
        $countries_by_label = array_reduce(
            array_keys($forms_bridge_dolibarr_countries),
            function ($countries, $country_id) {
                global $forms_bridge_dolibarr_countries;
                $label = $forms_bridge_dolibarr_countries[$country_id];
                $countries[$label] = $country_id;
                return $countries;
            },
            []
        );

        if (isset($countries_by_label[$payload['country_id']])) {
            $payload['country_id'] =
                $countries_by_label[$payload['country_id']];
        }
    }

    return $payload;
}

return [
    'title' => __('Country ID', 'forms-bridge'),
    'description' => __(
        'Ensures that country id is a valid and well known value',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_country_id',
    'input' => [
        [
            'name' => 'country_id',
            'type' => 'integer',
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'country_id',
            'type' => 'integer',
        ],
    ],
];
