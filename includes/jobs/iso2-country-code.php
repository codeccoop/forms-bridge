<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'name' => 'iso2-country-code',
    'title' => __('ISO2 country code', 'forms-bridge'),
    'description' => __(
        'Gets the ISO2 country code from country names and replace its value',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_job_iso2_country_code',
    'input' => [
        [
            'name' => 'country',
            'required' => true,
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'country',
            'schema' => ['type' => 'string'],
        ],
    ],
];

function forms_bridge_job_iso2_country_code($payload)
{
    global $forms_bridge_iso2_countries;
    $country_code = strtoupper($payload['country']);

    if (!isset($forms_bridge_iso2_countries[$country_code])) {
        $countries = [];
        foreach ($forms_bridge_iso2_countries as $country_code => $country) {
            $countries[$country] = $country_code;
        }

        if (isset($countries[$payload['country']])) {
            $payload['country'] = $countries[$payload['country']];
        } else {
            $payload['country'] = null;
        }
    } else {
        $payload['country'] = $country_code;
    }

    return $payload;
}
