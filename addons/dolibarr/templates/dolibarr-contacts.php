<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'dolibarr-contacts') {
            $index = array_search(
                'no_email',
                array_column($data['form']['fields'], 'name')
            );

            $field = &$data['form']['fields'][$index];
            $field['value'] = !$field['value'];
        }

        return $data;
    },
    10,
    2
);

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'dolibarr-contacts') {
            return $payload;
        }

        $response = $bridge->backend->get($bridge->endpoint, [
            'limit' => '1',
            'sqlfilters' => "(t.firstname:like:'{$payload['firstname']}') and (t.lastname:like:'{$payload['lastname']}') and (t.email:=:'{$payload['email']}')",
        ]);

        if (is_wp_error($response)) {
            $response_code = $response->get_error_data()['response'][
                'response'
            ]['code'];

            if ($response_code !== 404) {
                do_action(
                    'forms_bridge_on_failure',
                    $bridge,
                    $response,
                    $payload
                );

                return;
            }
        }

        if (!is_wp_error($response)) {
            return;
        }

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('Contacts', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Endpoint', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'value' => '/api/index.php/contacts',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Contacts', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'no_email',
            'label' => __('Subscrive to email', 'forms-bridge'),
            'type' => 'boolean',
            'default' => true,
        ],
    ],
    'form' => [
        'title' => __('Contacts', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'status',
                'value' => '1',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'no_email',
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
        ],
    ],
    'backend' => [
        'headers' => [
            'name' => 'Accept',
            'value' => 'application/json',
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/contacts',
        'mappers' => [
            [
                'from' => 'status',
                'to' => 'status',
                'cast' => 'string',
            ],
            [
                'from' => 'no_email',
                'to' => 'no_email',
                'cast' => 'string',
            ],
        ],
    ],
];
