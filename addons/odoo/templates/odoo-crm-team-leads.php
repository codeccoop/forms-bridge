<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'odoo-crm-team-leads') {
            return $payload;
        }

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-lead-team-owner',
                'template' => null,
                'method' => 'search',
                'model' => 'crm.team',
            ])
            ->submit([['name', '=', $payload['team']]]);

        if (is_wp_error($response)) {
            do_action('forms_bridge_on_failure', $bridge, $response, $payload);
            return;
        }

        $team_id = $response['data']['result'][0];
        $payload['team_id'] = $team_id;
        unset($payload['team']);

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-team-lead-contact-by-email',
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
                'is_company' => false,
                'name' => $payload['contact_name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? '',
            ];

            $response = $bridge
                ->patch([
                    'name' => 'odoo-rpc-create-team-lead-contact',
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
    'title' => __('CRM Team Leads', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('CRM Team Leads', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'team',
            'label' => __('Owner team', 'forms-bridge'),
            'description' => __(
                'Name of the owner team of the lead',
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
                'name' => 'team',
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
