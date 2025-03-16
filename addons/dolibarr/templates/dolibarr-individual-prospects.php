<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'dolibarr-individual-prospects') {
            return $payload;
        }

        $backend = $bridge->backend;
        $response = $backend->get('/api/index.php/thirdparties', [
            'sortfield' => 't.rowid',
            'sortorder' => 'DESC',
            'limit' => 1,
        ]);

        if (is_wp_error($response)) {
            do_action('forms_bridge_on_failure', $bridge, $response, $payload);

            return;
        }

        $previus_code_client = $response['data'][0]['code_client'];
        [$prefix, $number] = explode('-', $previus_code_client);

        $next = strval($number + 1);
        while (strlen($next) < strlen($number)) {
            $next = '0' . $next;
        }

        $payload['code_client'] = $prefix . '-' . $next;

        $payload['name'] = "{$payload['firstname']} {$payload['lastname']}";

        return $payload;
    },
    9,
    2
);

return [
    'title' => __('Dolibarr Individual Prospects', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Endpoint', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'value' => '/api/index.php/thirdparties',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'label' => __('Form title', 'forms-bridge'),
            'required' => true,
            'type' => 'string',
            'default' => __('Individual Leads', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'stcomm_id',
            'label' => __('Prospect status', 'forms-bridge'),
            'required' => true,
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Never contacted', 'forms-bridge'),
                    'value' => '0',
                ],
                [
                    'label' => __('To contact', 'forms-bridge'),
                    'value' => '1',
                ],
                [
                    'label' => __('Contact in progress', 'forms-bridge'),
                    'value' => '2',
                ],
                [
                    'label' => __('Contacted', 'forms-bridge'),
                    'value' => '3',
                ],
                [
                    'label' => __('Do not contact', 'forms-bridge'),
                    'value' => '-1',
                ],
            ],
            'default' => '0',
        ],
    ],
    'form' => [
        'title' => __('Individual Leads', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'status',
                'value' => '1',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'typent_id',
                'value' => '8',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'client',
                'value' => '2',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'stcomm_id',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'firstname',
                'label' => __('First name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'lastname',
                'label' => __('Last name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => __('Phone', 'forms-bridge'),
                'type' => 'text',
            ],
        ],
    ],
    'backend' => [
        'headers' => [
            'name' => 'Accept',
            'value' => 'application/json',
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/thirdparties',
        'method' => 'POST',
        'mappers' => [
            [
                'from' => 'status',
                'to' => 'status',
                'cast' => 'string',
            ],
            [
                'from' => 'typent_id',
                'to' => 'typent_id',
                'cast' => 'string',
            ],
            [
                'from' => 'client',
                'to' => 'client',
                'cast' => 'string',
            ],
            [
                'from' => 'stcomm_id',
                'to' => 'stcomm_id',
                'cast' => 'string',
            ],
        ],
    ],
];
