<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_mailchimp_backend_headers($headers)
{
    if (isset($headers['api-key'])) {
        $api_key = $headers['api-key'];
        unset($headers['api-key']);

        $headers['Authorization'] = 'Basic ' . base64_encode('key:' . $api_key);
    }

    remove_filter(
        'http_bridge_backend_headers',
        'forms_bridge_mailchimp_backend_headers',
        10,
        1
    );

    return $headers;
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'mailchimp-contacts') {
            $index = array_search(
                'datacenter',
                array_column($data['fields'], 'name')
            );

            $dc = $data['fields'][$index]['value'];
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

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'mailchimp-contacts') {
            return $payload;
        }

        $with_merge_fields = ['merge_fields' => []];

        foreach ($payload as $field => $value) {
            if ($field === 'email_address') {
                $with_merge_fields[$field] = $value;
            } elseif ($field === 'list_id' && $value) {
                $with_merge_fields[$field] = strval((int) $value);
            } elseif ($field === 'status') {
                $with_merge_fields[$field] = in_array($value, [
                    'subscribed',
                    'unsubscribed',
                    'cleaned',
                    'pending',
                    'transactional',
                ])
                    ? $value
                    : 'pending';
            } else {
                $with_merge_fields['merge_fields'][strtoupper($field)] = $value;
            }
        }

        $with_merge_fields['language'] = get_locale();

        add_filter(
            'http_bridge_backend_headers',
            'forms_bridge_mailchimp_backend_headers',
            10,
            1
        );

        return $with_merge_fields;
    },
    9,
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
            'value' => 'https://{dc}.api.mailchimp.com/3.0',
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
            'value' => '/lists/{list_id}/members',
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
            'ref' => '#form/fields[]',
            'name' => 'datacenter',
            'label' => __('Datacenter', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
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
                'name' => 'datacenter',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'list_id',
                'type' => 'hidden',
                'required' => true,
            ],
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
        'endpoint' => '/v3/contacts',
        'pipes' => [
            [
                'from' => 'datacenter',
                'to' => 'datacenter',
                'cast' => 'null',
            ],
            [
                'from' => 'list_id',
                'to' => 'list_id',
                'cast' => 'null',
            ],
        ],
    ],
];
