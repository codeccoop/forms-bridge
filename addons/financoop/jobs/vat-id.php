<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_financoop_vat_id($payload)
{
    global $forms_bridge_country_codes;

    $prefixed = preg_match('/^[A-Z]{2}/', $payload['vat'], $matches);

    $country_code = $payload['country_code'] ?? null;

    if ($prefixed) {
        $vat_prefix = $matches[0];
    } elseif ($country_code) {
        $vat_prefix = strtoupper($country_code);
    } else {
        $vat_prefix = strtoupper(explode('_', get_locale())[0]);
    }

    if (!isset($forms_bridge_country_codes[$vat_prefix])) {
        if (
            !$country_code ||
            !isset($forms_bridge_country_codes[$country_code])
        ) {
            return new WP_Error(
                'invalid_country_code',
                __('The vat ID prefix is invalid', 'forms-bridge')
            );
        }

        $prefixed = false;
        $vat_prefix = $country_code;
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
            'name' => 'vat',
            'schema' => ['type' => 'string'],
            'touch' => true,
        ],
        [
            'name' => 'country_code',
            'schema' => ['type' => 'string'],
        ],
    ],
];
