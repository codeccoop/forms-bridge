<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'bigin-deals') {
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
    'title' => __('Deals', 'forms-bridge'),
    'description' => __(
        'Leads form templates. The resulting bridge will convert form submissions into deals on the sales pipeline linked new contacts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/bigin/v2/Pipelines',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Owner.id',
            'label' => __('Owner', 'forms-bridge'),
            'descritpion' => __(
                'Email of the owner user of the deal',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Deal_Name',
            'label' => __('Deal name', 'forms-bridge'),
            'description' => __('Name of the pipeline deals', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Stage',
            'label' => __('Deal stage', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'value' => 'Qualification',
                    'label' => __('Qualification', 'forms-bridge'),
                ],
                [
                    'value' => 'Needs Analysis',
                    'label' => __('Needs Analysis', 'forms-bridge'),
                ],
                [
                    'value' => 'Proposal/Price Quote',
                    'label' => __('Proposal/Price Quote', 'forms-bridge'),
                ],
                [
                    'value' => 'Negotation/Review',
                    'label' => __('Negotiation/Review', 'forms-bridge'),
                ],
                [
                    'value' => 'Closed Won',
                    'label' => __('Closed Won', 'forms-bridge'),
                ],
                [
                    'value' => 'Closed Lost',
                    'label' => __('Closed Lost', 'forms-bridge'),
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Sub_Pipeline',
            'label' => __('Pipeline name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Amount',
            'label' => __('Deal amount', 'forms-bridge'),
            'type' => 'number',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'Tag',
            'label' => __('Deal tags', 'forms-bridge'),
            'description' => __(
                'Tag names separated by commas',
                'forms-bridge'
            ),
            'type' => 'string',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Deals', 'forms-bridge'),
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'Account_Name',
                'label' => __('Company name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Billing_Street',
                'label' => __('Street', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_Code',
                'label' => __('Postal code', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_City',
                'label' => __('City', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_State',
                'label' => __('State', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_Country',
                'label' => __('Country', 'forms-bridge'),
                'type' => 'text',
            ],
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
                'name' => 'Title',
                'label' => __('Title', 'forms-bridge'),
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
        'endpoint' => '/bigin/v2/Pipelines',
        'workflow' => ['account-name', 'contact-name'],
        'mutations' => [
            [
                [
                    'from' => 'Amount',
                    'to' => 'Amount',
                    'cast' => 'number',
                ],
            ],
        ],
    ],
];
