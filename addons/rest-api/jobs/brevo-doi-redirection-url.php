<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_brevo_doi_redirection_url($payload)
{
    $site_url = get_site_url();

    $payload['redirectionUrl'] = (string) filter_var(
        (string) $payload['redirectionUrl'],
        FILTER_SANITIZE_URL
    );

    $parsed = parse_url($payload['redirectionUrl']);

    if (!isset($parsed['host'])) {
        $payload['redirectionUrl'] =
            $site_url .
            '/' .
            preg_replace('/^\/+/', '', $payload['redirectionUrl']);
    }

    return $payload;
}

return [
    'title' => __('Brevo redirection URL', 'forms-bridge'),
    'description' => __(
        'Sanitize the redirection URL value and sets site host as domain if is a relative URL.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_doi_redirection_url',
    'input' => [
        [
            'name' => 'redirectionUrl',
            'type' => 'string',
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'redirectionUrl',
            'type' => 'string',
        ],
    ],
];
