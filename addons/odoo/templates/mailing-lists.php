<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'odoo-mailing-lists') {
            $index = array_search(
                'list_ids',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = $data['bridge']['custom_fields'][$index];

                for ($i = 0; $i < count($field['value']); $i++) {
                    $data['bridge']['custom_fields'][] = [
                        'name' => "list_ids[{$i}]",
                        'value' => $field['value'][$i],
                    ];

                    $data['bridge']['mutations'][0][] = [
                        'from' => "list_ids[{$i}]",
                        'to' => "list_ids[{$i}]",
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

return [
    'title' => __('Mailing Lists', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will convert form submissions into subscriptions to mailing lists.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Mailing Lists', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'mailing.contact',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'list_ids',
            'label' => __('Mailing lists', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
    ],
    'bridge' => [
        'endpoint' => 'mailing.contact',
        'workflow' => ['mailing-contact'],
        'mutations' => [
            [
                [
                    'from' => 'first_name',
                    'to' => 'name[0]',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'last_name',
                    'to' => 'name[1]',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'name',
                    'to' => 'name',
                    'cast' => 'concat',
                ],
            ],
        ],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('First name', 'forms-bridge'),
                'name' => 'first_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Last name', 'forms-bridge'),
                'name' => 'last_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
        ],
    ],
];
