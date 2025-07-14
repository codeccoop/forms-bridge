<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_defaults',
    function ($defaults, $addon, $schema) {
        if ($addon !== 'listmonk') {
            return $defaults;
        }

        return wpct_plugin_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'description' => __(
                            'Label of the Listmonk API backend connection',
                            'forms-bridge'
                        ),
                        'default' => 'Listmonk API',
                    ],
                    [
                        'ref' => '#backend/authentication',
                        'name' => 'type',
                        'label' => __('Authentication', 'forms-bridge'),
                        'type' => 'string',
                        'value' => 'Token',
                    ],
                    [
                        'ref' => '#backend/authentication',
                        'name' => 'client_id',
                        'label' => __('API user', 'forms-bridge'),
                        'description' => __(
                            'You have to generate an API user on your listmonk instance. See the <a href="https://listmonk.app/docs/roles-and-permissions/#api-users">documentation</a> for more information',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend/authentication',
                        'name' => 'client_secret',
                        'label' => __('API token', 'forms-bridge'),
                        'description' => __(
                            'Token of the API user. The token will be shown only once on user creation time, be sure to copy its value and store it in a save place',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
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
                            'Select, at least, one list that users will subscribe to',
                            'forms-bridge'
                        ),
                        'type' => 'options',
                        'options' => [
                            'endpoint' => '/api/lists',
                            'finger' => [
                                'value' => 'data.results[].id',
                                'label' => 'data.results[].name',
                            ],
                        ],
                        'is_multi' => true,
                        'required' => true,
                    ],
                ],
                'bridge' => [
                    'backend' => 'Listmonk API',
                    'endpoint' => '',
                    'method' => 'POST',
                ],
                'backend' => [
                    'name' => 'Listmonk',
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
                    'authentication' => [
                        'type' => 'Token',
                    ],
                ],
            ],
            $defaults,
            $schema
        );
    },
    10,
    3
);

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_id) {
        if (strpos($template_id, 'listmonk-') !== 0) {
            return $data;
        }

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

        return $data;
    },
    10,
    2
);
