<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'brevo-contacts-doi') {
            $index = array_search(
                'includeListIds',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = $data['bridge']['custom_fields'][$index];

                for ($i = 0; $i < count($field['value']); $i++) {
                    $data['bridge']['custom_fields'][] = [
                        'name' => "includeListIds[{$i}]",
                        'value' => $field['value'][$i],
                    ];

                    $data['bridge']['mutations'][0][] = [
                        'from' => "includeListIds[{$i}]",
                        'to' => "includeListIds[{$i}]",
                        'cast' => 'integer',
                    ];
                }

                array_splice($data['bridge']['custom_fields'], $index, 1);
                $data['bridge']['custom_fields'] = array_values(
                    $data['bridge']['custom_fields']
                );
            }

            $index = array_search(
                'redirectionUrl',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['bridge']['custom_fields'][$index];

                $field['value'] = (string) filter_var(
                    (string) $field['value'],
                    FILTER_SANITIZE_URL
                );

                $parsed = parse_url($field['value']);

                if (!isset($parsed['host'])) {
                    $site_url = get_site_url();

                    $field['value'] =
                        $site_url .
                        '/' .
                        preg_replace('/^\/+/', '', $field['value']);
                }
            }
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Subscription DOI', 'forms-bridge'),
    'description' => __(
        'Subscription form with double opt-in confirmation',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/v3/contacts/doubleOptinConfirmation',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'includeListIds',
            'label' => __('Lists', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'templateId',
            'label' => __('Double opt-in template', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'redirectionUrl',
            'label' => __('Redirection URL', 'forms-bridge'),
            'type' => 'string',
            'description' => __(
                'URL of the web page that user will be redirected to after clicking on the double opt in URL',
                'forms-bridge'
            ),
            'required' => true,
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Subscription DOI', 'forms-bridge'),
        ],
    ],
    'form' => [
        'title' => __('Subscription DOI', 'forms-bridge'),
        'fields' => [
            [
                'name' => 'email',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'fname',
                'label' => __('Your first name', 'forms-bridge'),
                'type' => 'text',
                'required' => false,
            ],
            [
                'name' => 'lname',
                'label' => __('Your last name', 'forms-bridge'),
                'type' => 'text',
            ],
        ],
    ],
    'backend' => [
        'base_url' => 'https://api.brevo.com',
        'headers' => [
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/v3/contacts/doubleOptinConfirmation',
        'custom_fields' => [
            [
                'name' => 'attributes.LANGUAGE',
                'value' => '$locale',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'templateId',
                    'to' => 'templateId',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'fname',
                    'to' => 'attributes.FNAME',
                    'cast' => 'string',
                ],
                [
                    'from' => 'lname',
                    'to' => 'attributes.LNAME',
                    'cast' => 'string',
                ],
            ],
        ],
    ],
];
