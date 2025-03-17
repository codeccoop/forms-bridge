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

            $field = &$data['form']['fields'][$index];
            $field['value'] = base64_encode($field['value']);
        }

        return $data;
    },
    10,
    2
);

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'odoo-crm-leads') {
            return $payload;
        }

        $payload['owner'] = base64_decode($payload['owner']);

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-lead-owner-by-email',
                'template' => null,
                'method' => 'search',
                'model' => 'res.users',
            ])
            ->submit([['email', '=', $payload['owner']]]);

        if (is_wp_error($response)) {
            do_action('forms_bridge_on_failure', $bridge, $response, $payload);
            return;
        }

        $user_id = $response['data']['result'][0];
        $payload['user_id'] = $user_id;
        unset($payload['owner']);

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-lead-contact-by-email',
                'template' => null,
                'method' => 'search',
                'model' => 'res.partner',
            ])
            ->submit([
                ['email', '=', $payload['email']],
                ['is_company', '=', false],
            ]);

        if (is_wp_error($response)) {
            $contact = [
                'name' => $payload['contact_name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? '',
            ];

            $response = $bridge
                ->patch([
                    'name' => 'odoo-rpc-create-lead-contact',
                    'template' => null,
                    'model' => 'res.partner',
                ])
                ->submit($contact);

            if (is_wp_error($response)) {
                do_action(
                    'forms_bridge_on_failure',
                    $bridge,
                    $response,
                    $payload
                );
                return;
            }

            $partner_id = $response['data']['result'];
        } else {
            $partner_id = $response['data']['result'][0];
        }

        $payload['partner_id'] = $partner_id;

        unset($payload['contact_name']);
        unset($payload['email']);
        unset($payload['phone']);

        return $payload;
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
            'ref' => '#form/fields[]',
            'name' => 'owner',
            'label' => __('Owner email', 'forms-bridge'),
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
        'mappers' => [
            [
                'from' => 'priority',
                'to' => 'priority',
                'cast' => 'string',
            ],
        ],
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
