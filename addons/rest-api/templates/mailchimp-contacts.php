<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'rest-api-mailchimp-contacts') {
            $index = array_search(
                'datacenter',
                array_column($data['backend']['headers'], 'name')
            );

            $dc = $data['backend']['headers'][$index]['value'];
            $data['backend']['base_url'] = preg_replace(
                '/\{dc\}/',
                $dc,
                $data['backend']['base_url']
            );

            $index = array_search(
                'list_id',
                array_column($data['fields'], 'name')
            );

            $list_id = $data['fields'][$index]['value'];
            $data['bridge']['endpoint'] = preg_replace(
                '/\{list_id\}/',
                $list_id,
                $data['bridge']['endpoint']
            );
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('MailChimp Contacts', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#backend',
            'name' => 'base_url',
            'label' => __('MailChimp API URL', 'forms-bridge'),
            'type' => 'string',
            'value' => 'https://{dc}.api.mailchimp.com',
        ],
        [
            'ref' => '#backend',
            'name' => 'name',
            'label' => __(
                'Label of the MailChimp API backend connection',
                'forms-bridge'
            ),
            'type' => 'string',
            'default' => 'MailChimp API',
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
            'value' => '/3.0/lists/{list_id}/members',
        ],
        [
            'ref' => '#backend/headers[]',
            'name' => 'api-key',
            'label' => __('MailChimp API Key', 'forms-bridge'),
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
            'default' => __('Newsletter', 'forms-bridge'),
        ],
        [
            'ref' => '#backend/headers[]',
            'name' => 'datacenter',
            'label' => __('Datacenter', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => 'us1',
                    'value' => 'us1',
                ],
                [
                    'label' => 'us2',
                    'value' => 'us2',
                ],
                [
                    'label' => 'us3',
                    'value' => 'us3',
                ],
                [
                    'label' => 'us4',
                    'value' => 'us4',
                ],
                [
                    'label' => 'us5',
                    'value' => 'us5',
                ],
                [
                    'label' => 'us6',
                    'value' => 'us6',
                ],
                [
                    'label' => 'us7',
                    'value' => 'us7',
                ],
                [
                    'label' => 'us8',
                    'value' => 'us8',
                ],
                [
                    'label' => 'us9',
                    'value' => 'us9',
                ],
                [
                    'label' => 'us10',
                    'value' => 'us10',
                ],
                [
                    'label' => 'us11',
                    'value' => 'us11',
                ],
                [
                    'label' => 'us12',
                    'value' => 'us12',
                ],
                [
                    'label' => 'us13',
                    'value' => 'us13',
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#bridge',
            'name' => 'list_id',
            'label' => __('Audience ID', 'forms-bridge'),
            'description' => __(
                'You can find the ID on the settings tab of your segments',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'status',
            'label' => __('Subscription status', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Subscribed', 'forms-bridge'),
                    'value' => 'subscribed',
                ],
                [
                    'label' => __('Unsubscribed', 'forms-bridge'),
                    'value' => 'unsubscribed',
                ],
                [
                    'label' => __('Pending', 'forms-bridge'),
                    'value' => 'pending',
                ],
                [
                    'label' => __('Cleaned', 'forms-bridge'),
                    'value' => 'cleand',
                ],
                [
                    'label' => __('Transactional', 'forms-bridge'),
                    'value' => 'transactional',
                ],
            ],
            'default' => 'subscribed',
            'required' => true,
        ],
    ],
    'form' => [
        'title' => __('MailChimp Contacts', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'status',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'email_address',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'fname',
                'label' => __('Your first name', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'lname',
                'label' => __('Your last name', 'forms-bridge'),
                'type' => 'text',
            ],
        ],
    ],
    'backend' => [
        'base_url' => 'https://{dc}.api.mailchimp.com/3.0',
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/3.0/lists/{list_id}/members',
        'mutations' => [
            [
                [
                    'from' => 'fname',
                    'to' => 'merge_fields.FNAME',
                    'cast' => 'string',
                ],
                [
                    'from' => 'lname',
                    'to' => 'merge_fields.LNAME',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'locale',
                    'to' => 'language',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => [
            'forms-bridge-current-locale',
            'rest-api-mailchimp-contact-status',
            'rest-api-mailchimp-authorization',
        ],
    ],
];
