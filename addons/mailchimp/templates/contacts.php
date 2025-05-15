<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'mailchimp-contacts') {
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

            array_splice($data['backend']['headers'], $index, 1);

            $index = array_search(
                'list_id',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            $list_id = $data['bridge']['custom_fields'][$index]['value'];
            $data['bridge']['endpoint'] = preg_replace(
                '/\{list_id\}/',
                $list_id,
                $data['bridge']['endpoint']
            );

            array_splice($data['bridge']['custom_fields'], $index, 1);

            $index = array_search(
                'tags',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['bridge']['custom_fields'][$index];

                $tags = array_filter(
                    array_map('trim', explode(',', strval($field['value'])))
                );
                for ($i = 0; $i < count($tags); $i++) {
                    $data['bridge']['custom_fields'][] = [
                        'name' => "tags[{$i}]",
                        'value' => $tags[$i],
                    ];
                }

                array_splice($data['bridge']['custom_fields'], $index, 1);
            }

            $data['bridge']['custom_fields'] = array_values(
                $data['bridge']['custom_fields']
            );
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Contacts', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will convert form submissions into new list subscriptions.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/3.0/lists/{list_id}/members',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'list_id',
            'label' => __('Audience', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
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
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tags',
            'label' => __('Subscription tags', 'forms-bridge'),
            'description' => __(
                'Tag names separated by commas',
                'forms-bridge'
            ),
            'type' => 'string',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Contacts', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Contacts', 'forms-bridge'),
        'fields' => [
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
    'backend' => [],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/3.0/lists/{list_id}/members',
        'custom_fields' => [
            [
                'name' => 'language',
                'value' => '$locale',
            ],
            [
                'name' => 'ip_signup',
                'value' => '$ip_address',
            ],
            [
                'name' => 'timestamp_signup',
                'value' => '$iso_date',
            ],
        ],
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
        ],
    ],
];
