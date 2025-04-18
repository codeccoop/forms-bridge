<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'brevo-contacts') {
            $index = array_search(
                'listIds',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = $data['bridge']['custom_fields'][$index];

                for ($i = 0; $i < count($field['value']); $i++) {
                    $data['bridge']['custom_fields'][] = [
                        'name' => "listIds[{$i}]",
                        'value' => $field['value'][$i],
                    ];

                    $data['bridge']['mutations'][0][] = [
                        'from' => "listIds[{$i}]",
                        'to' => "listIds[{$i}]",
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
    'title' => __('Contacts', 'forms-bridge'),
    'description' => __('Contact form', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/v3/contacts',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'listIds',
            'label' => __('Segment IDs', 'forms-bridge'),
            'type' => 'string',
            'description' => __(
                'List IDs separated by commas. Leave it empty if you don\'t want to subscrive contact to any list',
                'forms-bridge'
            ),
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
        'endpoint' => '/v3/contacts',
        'custom_fields' => [
            [
                'name' => 'attributes.LANGUAGE',
                'value' => '$locale',
            ],
        ],
        'mutations' => [
            [
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
    ],
];
