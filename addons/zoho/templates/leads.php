<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'zoho-leads') {
            $index = array_search(
                'Tag',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['bridge']['custom_fields'][$index];

                if (!empty($field['value'])) {
                    $tags = array_filter(
                        array_map('trim', explode(',', strval($field['value'])))
                    );

                    for ($i = 0; $i < count($tags); $i++) {
                        $data['bridge']['custom_fields'][] = [
                            'name' => "Tag[{$i}].name",
                            'value' => $tags[$i],
                        ];
                    }
                }

                array_splice($data['bridge']['custom_fields'], $index, 1);
            }
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Leads', 'forms-bridge'),
    'description' => __(
        'Lead form template. The resulting bridge will convert form submissions into leads.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/crm/v7/Leads/upsert',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Owner.id',
            'label' => __('Owner', 'forms-bridge'),
            'description' => __(
                'Email of the owner user of the deal',
                'forms-bridge'
            ),
            'type' => 'string',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Lead_Source',
            'label' => __('Lead source', 'forms-bridge'),
            'description' => __(
                'Label to identify your website sourced leads',
                'forms-bridge'
            ),
            'type' => 'string',
            'default' => 'WordPress',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Lead_Status',
            'label' => __('Lead status', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Not Contacted', 'forms-bridge'),
                    'value' => 'Not Connected',
                ],
                [
                    'label' => __('Qualified', 'forms-bridge'),
                    'value' => 'Qualified',
                ],
                [
                    'label' => __('Not qualified', 'forms-bridge'),
                    'value' => 'Not Qualified',
                ],
                [
                    'label' => __('Pre-qualified', 'forms-bridge'),
                    'value' => 'Pre-Qualified',
                ],
                [
                    'label' => __('Attempted to Contact', 'forms-bridge'),
                    'value' => 'New Lead',
                ],
                [
                    'label' => __('Contact in Future', 'forms-bridge'),
                    'value' => 'Connected',
                ],
                [
                    'label' => __('Junk Lead', 'forms-bridge'),
                    'value' => 'Junk Lead',
                ],
                [
                    'label' => __('Lost Lead', 'forms-bridge'),
                    'value' => 'Lost Lead',
                ],
            ],
            'default' => 'Not Contacted',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Tag',
            'label' => __('Lead tags', 'forms-bridge'),
            'description' => __(
                'Tag names separated by commas',
                'forms-bridge'
            ),
            'type' => 'string',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Leads', 'forms-bridge'),
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'First_Name',
                'label' => __('First name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Last_Name',
                'label' => __('Last name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'Phone',
                'label' => __('Phone', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Description',
                'label' => __('Comments', 'forms-bridge'),
                'type' => 'textarea',
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/crm/v7/Leads/upsert',
        'scope' => 'ZohoCRM.modules.ALL',
    ],
];
