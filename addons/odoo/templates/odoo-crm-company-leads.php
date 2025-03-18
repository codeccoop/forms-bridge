<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_odoo_countries;

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'odoo-crm-company-leads') {
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
        if ($bridge->template !== 'odoo-crm-company-leads') {
            return $payload;
        }

        global $forms_bridge_odoo_countries;

        if (!isset($forms_bridge_odoo_countries[$payload['country_code']])) {
            $countries_by_label = array_reduce(
                array_keys($forms_bridge_odoo_countries),
                function ($countries, $country_code) {
                    global $forms_bridge_odoo_countries;
                    $label = $forms_bridge_odoo_countries[$country_code];
                    $countries[$label] = $country_code;
                    return $countries;
                },
                []
            );

            $payload['country_code'] =
                $countries_by_label[$payload['country_code']];
        }

        $vat_locale = strtoupper(substr($payload['vat'], 0, 2));

        if (!isset($forms_bridge_odoo_countries[$vat_locale])) {
            $payload['vat'] = $payload['country_code'] . $payload['vat'];
        }

        $payload['owner'] = base64_decode($payload['owner']);

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-company-lead-owner-by-email',
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
                'name' => 'odoo-rpc-search-lead-company-by-vat',
                'template' => null,
                'method' => 'search',
                'model' => 'res.partner',
            ])
            ->submit([
                ['vat', '=', $payload['vat']],
                ['is_company', '=', true],
            ]);

        if (is_wp_error($response)) {
            $company = [
                'is_company' => true,
                'vat' => $payload['vat'],
                'name' => $payload['company_name'],
                'street' => $payload['street'],
                'city' => $payload['city'],
                'zip' => $payload['zip'],
                'country_code' => $payload['country_code'],
            ];

            $response = $bridge
                ->patch([
                    'name' => 'odoo-rpc-create-company-lead-company',
                    'template' => null,
                    'model' => 'res.partner',
                ])
                ->submit($company);

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

        unset($payload['vat']);
        unset($payload['company_name']);
        unset($payload['street']);
        unset($payload['city']);
        unset($payload['zip']);
        unset($payload['country_code']);

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-company-lead-contact-by-email',
                'template' => null,
                'method' => 'search',
                'model' => 'res.partner',
            ])
            ->submit([
                ['email', '=', $payload['email']],
                ['parent_id', '=', $partner_id],
            ]);

        if (is_wp_error($response)) {
            $contact = [
                'name' => $payload['contact_name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? '',
                'function' => $payload['function'],
                'parent_id' => $partner_id,
            ];

            $response = $bridge
                ->patch([
                    'name' => 'odoo-rpc-create-company-lead-contact',
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
        }

        $payload['email_from'] = $payload['email'];

        unset($payload['contact_name']);
        unset($payload['email']);
        unset($payload['phone']);
        unset($payload['function']);

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('CRM Company Leads', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('CRM Company Leads', 'forms-bridge'),
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
                'label' => __('Company name', 'forms-bridge'),
                'name' => 'company_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Tax ID', 'forms-bridge'),
                'name' => 'vat',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'street',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Country', 'forms-bridge'),
                'name' => 'country_code',
                'type' => 'options',
                'options' => array_map(function ($country_code) {
                    global $forms_bridge_odoo_countries;
                    return [
                        'value' => $country_code,
                        'label' => $forms_bridge_odoo_countries[$country_code],
                    ];
                }, array_keys($forms_bridge_odoo_countries)),
                'required' => true,
            ],
            [
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'contact_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Job position', 'forms-bridge'),
                'name' => 'function',
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
