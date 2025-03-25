<?php

function forms_bridge_mailchimp_current_language($payload)
{
    $payload['language'] = $payload['language'] ?? get_locale();
    return $payload;
}

return [
    'title' => __('MailChimp current language', 'forms-bridge'),
    'description' => __(
        'Adds the current language\'s locale as the value of the "language" attribute if it doesn\'t exists',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_mailchimp_current_language',
    'input' => [],
    'output' => [
        [
            'name' => 'language',
            'type' => 'string',
        ],
    ],
];
