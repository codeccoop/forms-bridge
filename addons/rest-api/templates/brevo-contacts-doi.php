<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template === 'brevo-contacts-doi') {
            $payload['includeListIds'] = array_map(
                'intval',
                explode(',', $payload['includeListIds'])
            );

            $url = parse_url($payload['redirectionUrl']);
            if (!isset($url['host'])) {
                $payload['redirectionUrl'] =
                    get_site_url() .
                    '/' .
                    preg_replace('/^\//', '', $payload['redirectionUrl']);
            }
        }

        return $payload;
    },
    9,
    2
);

return [
    'title' => __('Brevo Contacts DOI', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#backend',
            'name' => 'base_url',
            'label' => __('Brevo API URL', 'forms-bridge'),
            'type' => 'string',
            'value' => 'https://api.brevo.com/v3/',
        ],
        [
            'ref' => '#backend',
            'name' => 'name',
            'label' => __(
                'Label of the Brevo API backend connection',
                'forms-bridge'
            ),
            'type' => 'string',
            'default' => 'Brevo API',
        ],
        [
            'ref' => '#bridge',
            'name' => 'method',
            'label' => __('Bridge HTTP method', 'forms-bridge'),
            'type' => 'string',
            'value' => 'POST',
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Bridge endpoint', 'forms-bridge'),
            'type' => 'string',
            'value' => '/v3/contacts/doubleOptinConfirmation',
        ],
        [
            'ref' => '#backend/headers[]',
            'name' => 'api-key',
            'label' => __('Brevo API Key', 'forms-bridge'),
            'description' => __(
                'You can get it from "SMTP & API" > "API Keys" page from your dashboard',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Contacts DOI', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'includeListIds',
            'label' => __('Segment IDs', 'forms-bridge'),
            'type' => 'string',
            'description' => __(
                'List IDs separated by commas. Leave it empty if you don\'t want to subscrive contact to any list',
                'forms-bridge'
            ),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'templateId',
            'label' => __('Double opt-in template ID', 'forms-bridge'),
            'type' => 'string',
            'description' => __(
                'List IDs separated by commas. Leave it empty if you don\'t want to subscrive contact to any list',
                'forms-bridge'
            ),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'redirectionUrl',
            'label' => __('Redirection URL', 'forms-bridge'),
            'type' => 'string',
            'description' => __(
                'URL of the web page that user will be redirected to after clicking on the double opt in URL',
                'forms-bridge'
            ),
        ],
    ],
    'form' => [
        'title' => __('Brevo Contacts DOI', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'includeListIds',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'templateId',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'redirectionUrl',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'fname',
                'label' => __('Your first name', 'forms-bridge'),
                'type' => 'text',
                'required' => false,
            ],
            [
                'name' => 'lname',
                'label' => __('Your last name', 'forms-bridge'),
                'type' => 'text',
            ],
        ],
    ],
    'backend' => [
        'base_url' => 'https://api.brevo.com/v3/',
        'headers' => [
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/v3/contacts/doubleOptinConfirmation',
        'mappers' => [
            [
                'from' => 'templateId',
                'to' => 'templateId',
                'cast' => 'integer',
            ],
            [
                'from' => 'fname',
                'to' => 'attributes.FNAME',
                'cast' => 'string',
            ],
            [
                'from' => 'lname',
                'to' => 'attributes.LNAME',
                'cast' => 'string',
            ],
        ],
    ],
];
