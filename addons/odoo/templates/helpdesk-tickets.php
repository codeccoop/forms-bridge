<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Helpdesk ticket', 'forms-bridge'),
    'description' => __(
        'Convert form submissions to helpdesk tickets',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Helpdesk', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'helpdesk.ticket',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'team_id',
            'label' => __('Owner team', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                'endpoint' => 'helpdesk.ticket.team',
                'finger' => [
                    'value' => 'result.[].id',
                    'label' => 'result.[].name',
                ],
            ],
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'channel_id',
            'label' => __('Incoming channel', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                'endpoint' => 'helpdesk.ticket.channel',
                'finger' => [
                    'value' => 'result.[].id',
                    'label' => 'result.[].name',
                ],
            ],
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'user_id',
            'label' => __('Owner user', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                'endpoint' => 'res.user',
                'finger' => [
                    'value' => 'result.[].id',
                    'label' => 'result.[].name',
                ],
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => 'helpdesk.ticket',
        'mutations' => [
            [
                [
                    'from' => 'your-name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'name',
                    'to' => 'partner_name',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'your-email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
                [
                    'from' => 'email',
                    'to' => 'partner_email',
                    'cast' => 'copy',
                ],
            ],
            [
                [
                    'from' => 'ticket-name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => ['contact'],
    ],
    'form' => [
        'title' => __('Helpdesk', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'your-name',
                'label' => __('Your name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'your-email',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'ticket-name',
                'label' => __('Subject', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'description',
                'label' => __('Message', 'forms-bridge'),
                'type' => 'textarea',
                'required' => true,
            ],
        ],
    ],
];
