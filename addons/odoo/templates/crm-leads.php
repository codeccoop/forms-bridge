<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'odoo-crm-leads') {
            $index = array_search(
                'tag_ids',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = $data['bridge']['custom_fields'][$index];
                $tags = $field['value'] ?? [];

                for ($i = 0; $i < count($tags); $i++) {
                    $data['bridge']['custom_fields'][] = [
                        'name' => "tag_ids[{$i}]",
                        'value' => $tags[$i],
                    ];

                    $data['bridge']['mutations'][0][] = [
                        'from' => "tag_ids[{$i}]",
                        'to' => "tag_ids[{$i}]",
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
    'title' => __('CRM Leads', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('CRM Leads', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'crm.lead',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'user_id',
            'label' => __('Owner email', 'forms-bridge'),
            'description' => __(
                'Email of the owner user of the lead',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'lead_name',
            'label' => __('Lead name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => __('Web Lead', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'priority',
            'label' => __('Priority', 'forms-bridge'),
            'type' => 'number',
            'min' => 0,
            'max' => 3,
            'default' => 1,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tag_ids',
            'label' => __('Lead tags', 'forms-bridge'),
            'type' => 'string',
        ],
    ],
    'bridge' => [
        'endpoint' => 'crm.lead',
        'mutations' => [
            [
                [
                    'from' => 'user_id',
                    'to' => 'user_id',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'contact_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'lead_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => ['odoo-crm-contact'],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'contact_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Your email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Your phone', 'forms-bridge'),
                'name' => 'phone',
                'type' => 'text',
            ],
            [
                'label' => __('Comments', 'forms-bridge'),
                'name' => 'description',
                'type' => 'textarea',
                'required' => true,
            ],
        ],
    ],
];
