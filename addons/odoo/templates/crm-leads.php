<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'odoo-crm-leads') {
            $index = array_search(
                'owner',
                array_column($data['form']['fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['form']['fields'][$index];
                $field['value'] = base64_encode($field['value']);
            }
        }

        return $data;
    },
    10,
    2
);

add_filter(
    'forms_bridge_workflow_job_payload',
    function ($payload, $job, $bridge) {
        if (
            $job->name === 'odoo-lead-owner-id' &&
            $bridge->template === 'odoo-crm-leads'
        ) {
            if (isset($payload['owner_email'])) {
                $payload['owner_email'] = base64_decode(
                    $payload['owner_email']
                );
            } elseif (isset($payload['owner'])) {
                $payload['owner'] = base64_decode($payload['owner']);
            }
        }

        return $payload;
    },
    5,
    3
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
            'ref' => '#form/fields[]',
            'name' => 'owner',
            'label' => __('Owner email', 'forms-bridge'),
            'description' => __(
                'Email of the owner user of the lead',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'name',
            'label' => __('Lead name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => __('Web Lead', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'priority',
            'label' => __('Priority', 'forms-bridge'),
            'type' => 'number',
            'min' => 0,
            'max' => 3,
            'required' => true,
            'default' => 1,
        ],
    ],
    'bridge' => [
        'model' => 'crm.lead',
        'mutations' => [
            [
                [
                    'from' => 'priority',
                    'to' => 'priority',
                    'cast' => 'string',
                ],
                [
                    'from' => 'owner',
                    'to' => 'owner_email',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => ['odoo-lead-owner-id', 'odoo-contact-id'],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'name',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'owner',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'priority',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'type',
                'type' => 'hidden',
                'value' => 'opportunity',
                'required' => true,
            ],
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
