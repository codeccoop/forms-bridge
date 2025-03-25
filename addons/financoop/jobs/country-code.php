<?php

function forms_bridge_financoop_country_code($payload)
{
    global $forms_bridge_odoo_countries;
    $country = strtoupper($payload['country']);

    if (!isset($forms_bridge_odoo_countries[$country])) {
        $countries_by_label = array_reduce(
            array_keys($forms_bridge_odoo_countries),
            function ($labels, $country_code) {
                global $forms_bridge_odoo_countries;
                $label = $forms_bridge_odoo_countries[$country_code];
                $labels[$label] = $country_code;
                return $labels;
            },
            []
        );

        $payload['country_code'] =
            $countries_by_label[$payload['country_code']];
    } else {
        $payload['country_code'] = $country;
    }

    return $payload;
}

return [
    'title' => __('Odoo country code', 'forms-bridge'),
    'description' => __(
        'Gets the ISO2 country code from country names and replace its value',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_financoop_country_code',
    'input' => [
        [
            'name' => 'country',
            'type' => 'string',
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'country_code',
            'type' => 'string',
            'required' => true,
        ],
    ],
];
