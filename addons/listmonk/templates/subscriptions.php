<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'listmonk-subscriptions') {
            $index = array_search(
                'lists',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['bridge']['custom_fields'][$index];
                if (is_array($field['value'])) {
                    for ($i = 0; $i < count($field['value']); $i++) {
                        $data['bridge']['custom_fields'][] = [
                            'name' => "lists[{$i}]",
                            'value' => (int) $field['value'][$i],
                        ];

                        $data['bridge']['mutations'][0][] = [
                            'from' => "lists[{$i}]",
                            'to' => "lists[{$i}]",
                            'cast' => 'integer',
                        ];
                    }

                    array_splice($data['bridge']['custom_fields'], $index, 1);
                    $data['bridge']['custom_fields'] = array_values(
                        $data['bridge']['custom_fields']
                    );
                }
            }
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Subscriptions', 'forms-bridge'),
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
            'ref' => '#backend/headers[]',
            'name' => 'api_user',
            'description' => __(
                'You have to generate an API user on your listmonk instance. See the <a href="https://listmonk.app/docs/roles-and-permissions/#api-users">documentation</a> for more information',
                'forms-bridge'
            ),
        ],
        [
            'ref' => '#backend/headers[]',
            'name' => 'token',
            'description' => __(
                'Token of the API user. The token will be shown only once on user creation time, be sure to copy its value and store it in a save place',
                'forms-bridge'
            ),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Bridge endpoint', 'forms-bridge'),
            'type' => 'string',
            'value' => '/api/subscribers',
        ],
        [
            'ref' => '#bridge',
            'name' => 'method',
            'value' => 'POST',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'lists',
            'label' => __('Mailing lists', 'forms-bridge'),
            'description' => __(
                'Select, at least, one list that users will be subscribed to',
                'forms-bridge'
            ),
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'status',
            'label' => __('Subscription status', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Enabled', 'forms-bridge'),
                    'value' => 'enabled',
                ],
                [
                    'label' => __('Disabled', 'forms-bridge'),
                    'value' => 'blocklisted',
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Subscriptions', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Subscriptions', 'forms-bridge'),
        'fields' => [
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
                'value' => 'application/json',
            ],
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/api/subscribers',
        'custom_fields' => [
            [
                'name' => 'locale',
                'value' => '$locale',
            ],
            [
                'name' => 'preconfirm_subscriptions',
                'value' => '1',
            ],
        ],
        'mutations' => [
            [
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
                [
                    'from' => 'locale',
                    'to' => 'attribs.locale',
                    'cast' => 'string',
                ],
                [
                    'from' => 'preconfirm_subscriptions',
                    'to' => 'preconfirm_subscriptions',
                    'cast' => 'boolean',
                ],
            ],
        ],
    ],
];
