<?php

function forms_bridge_brevo_doi_redirection_url($payload)
{
    if (!isset($payload['redirectionUrl'])) {
        return $payload;
    }

    $site_url = get_site_url();
    $payload['redirectionUrl'] = filter_var(
        $payload['redirectionUrl'],
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
    'title' => __('Brevo DOI redirection URL', 'forms-bridge'),
    'description' => __(
        'Sanitize the redirection URL value and sets site host as domain if is a relative URL.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_doi_redirection_url',
    'input' => ['redirectionUrl'],
    'output' => ['redirectionUrl'],
];
