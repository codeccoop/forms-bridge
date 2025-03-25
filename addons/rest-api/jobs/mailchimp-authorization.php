<?php

function forms_bridge_mailchimp_backend_headers($headers)
{
    remove_filter(
        'forms_bridge_backend_headers',
        'forms_bridge_mailchimp_backend_headers',
        10,
        1
    );

    if (isset($headers['api-key'])) {
        $api_key = $headers['api-key'];
        unset($headers['api-key']);

        $headers['Authorization'] = 'Basic ' . base64_encode('key:' . $api_key);
    }

    return $headers;
}

function forms_bridge_mailchimp_authorization()
{
    add_filter(
        'http_bridge_backend_headers',
        'forms_bridge_mailchimp_backend_headers',
        10,
        1
    );
}

return [
    'title' => __('MailChimp authorization', 'forms-bridge'),
    'description' => __(
        'Intercepts http headers and sets up MailChimp basic authorization credentials.',
        'forms-bridge'
    ),
    'input' => [],
    'output' => [],
    'submission_callbacks' => [
        'before' => 'forms_bridge_mailchimp_authorization',
    ],
];
