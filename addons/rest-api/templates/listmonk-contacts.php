<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Listmonk Contacts', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#backend',
            'name' => 'base_url',
            'label' => __('Listmonk URL', 'forms-bridge'),
            'description' => __(
                'Insert the base URL of your listmonk server',
                'forms-bridge'
            ),
            'type' => 'string',
            'default' => 'https://',
        ],
        [
            'ref' => '#backend',
            'name' => 'name',
            'label' => __(
                'Label of the Listmonk backend connection',
                'forms-bridge'
            ),
            'type' => 'string',
            'default' => 'Listmonk',
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
            'value' => '/subscription/form',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Newsletter', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'l',
            'label' => __('List ID', 'forms-bridge'),
            'type' => 'string',
            'description' => __(
                'Go to Lists > Forms and copy the value of the checkbox input with name "l"',
                'forms-bridge'
            ),
        ],
    ],
    'form' => [
        'title' => __('Listmonk Contacts', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'nonce',
                'type' => 'hidden',
                'value' => '',
                'required' => true,
            ],
            [
                'name' => 'l',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'your-email',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'your-name',
                'label' => __('Your name', 'forms-bridge'),
                'type' => 'text',
            ],
        ],
    ],
    'backend' => [
        'headers' => [
            [
                'name' => 'Content-Type',
                'value' => 'application/x-www-form-urlencoded',
            ],
            [
                'name' => 'Accept',
                'value' => 'text/html,application/xhtml+xml,application/xml',
            ],
        ],
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/subscription/form',
        'mappers' => [
            [
                'from' => 'your-email',
                'to' => 'email',
                'cast' => 'string',
            ],
            [
                'from' => 'your-name',
                'to' => 'name',
                'cast' => 'string',
            ],
        ],
    ],
];
