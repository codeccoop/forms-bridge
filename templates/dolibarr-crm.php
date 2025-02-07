<?php

add_filter(
    'forms_bridge_payload',
    function ($payload, $hook) {
        if ($hook->template === 'dolibarr-crm') {
            // do something
        }

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('Dolibarr CRM', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'label' => __('Form title', 'forms-bridge'),
            'default' => __('Dolibarr CRM Leads', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'campaign',
            'label' => __('Campaign', 'forms-bridge'),
            'type' => 'string',
        ],
        [
            'ref' => '#hook',
            'name' => 'endpoint',
            'label' => __('Endpoint', 'forms-bridge'),
            'type' => 'string',
            'default' => '/api/index.php/products',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Campaign', 'forms-bridge'),
                'name' => 'campaign',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'label' => __('Email', 'forms-bridge'),
                'name' => 'email_from',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Message', 'forms-bridge'),
                'name' => 'message',
                'type' => 'textarea',
                'required' => true,
            ],
        ],
    ],
    'hook' => [
        'pipes' => [
            [
                'from' => 'submission_id',
                'to' => 'submission_id',
                'cast' => 'null',
            ],
            [
                'from' => 'campaign',
                'to' => 'DynamicField.Campaign',
                'cast' => 'string',
            ],
            [
                'from' => 'message',
                'to' => 'DynamicField.Message',
                'cast' => 'string',
            ],
        ],
    ],
];
