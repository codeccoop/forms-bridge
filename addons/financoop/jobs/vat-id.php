<?php

function forms_bridge_financoop_vat_id($payload)
{
    global $forms_bridge_odoo_countries;

    $prefixed = preg_match('/^[A-Z]{2}/', $payload['vat'], $matches);

    if ($prefixed) {
        $vat_prefix = $matches[0];
    } elseif (isset($payload['country_code'])) {
        $vat_prefix = strtoupper($payload['country_code']);
    } else {
        $vat_prefix = strtoupper(explode('_', get_locale())[0]);
    }

    if (!isset($forms_bridge_odoo_countries[$vat_prefix])) {
        return new WP_Error(
            'invalid_country_code',
            __('The vat ID prefix is invalid', 'forms-bridge')
        );
    }

    if (!$prefixed) {
        $payload['vat'] = $vat_prefix . $payload['vat'];
    }

    return $payload;
}

return [
    'title' => __('Prefixed vat ID', 'forms-bridge'),
    'description' => __(
        'Prefix the vat with country code, or the current locale, if it isn\'t prefixed',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_financoop_vat_id',
    'input' => [
        [
            'name' => 'vat',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'country_code',
            'type' => 'string',
        ],
    ],
    'output' => [
        [
            'name' => 'vat',
            'type' => 'string',
        ],
        [
            'name' => 'country_code',
            'type' => 'string',
        ],
    ],
];
