<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'odoo-mailing-lists') {
            return $payload;
        }

        $payload['list_ids'] = array_map(function ($list_id) {
            return (int) $list_id;
        }, explode(',', $payload['list_ids']));

        $payload['name'] = "{$payload['first_name']} {$payload['last_name']}";

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-mailing-contact-by-email',
                'template' => null,
                'method' => 'search',
                'model' => 'mailing.contact',
            ])
            ->submit([['email', '=', $payload['email']]]);

        if (!is_wp_error($response)) {
            $contact_id = $response['data']['result'][0];
            $list_ids = $payload['list_ids'];

            $response = $bridge
                ->patch([
                    'name' => 'odoo-rpc-get-mailing-contact-subscriptions',
                    'template' => null,
                    'model' => 'mailing.subscription',
                    'method' => 'search_read',
                ])
                ->submit([['contact_id', '=', $contact_id]]);

            if (!is_wp_error($response)) {
                foreach ($response['data']['result'] as $subscription) {
                    if (!in_array($subscription['list_id'][0], $list_ids)) {
                        $list_ids[] = $subscription['id'];
                    }
                }
            }

            add_filter(
                'forms_bridge_rpc_payload',
                function ($payload, $bridge) use ($list_ids) {
                    if (
                        $bridge->name ===
                        'odoo-rpc-update-mailing-contact-subscriptions'
                    ) {
                        $payload['params']['args'][] = [
                            'list_ids' => $list_ids,
                        ];
                    }

                    return $payload;
                },
                10,
                2
            );

            $response = $bridge
                ->patch([
                    'name' => 'odoo-rpc-update-mailing-contact-subscriptions',
                    'template' => null,
                    'model' => 'mailing.contact',
                    'method' => 'write',
                ])
                ->submit([$contact_id]);

            if (!is_wp_error($response)) {
                return;
            }
        }

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('Mailing Lists', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Mailing Lists', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'list_ids',
            'label' => __('List IDs', 'forms-bridge'),
            'description' => __('List IDs separated by commas', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
    ],
    'bridge' => [
        'model' => 'mailing.contact',
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'list_ids',
                'type' => 'hidden',
                'required' => true,
            ],
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
