<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'holded-company-contacts') {
            $index = array_search(
                'tags',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['bridge']['custom_fields'][$index];

                if (!empty($field['value'])) {
                    $tags = array_filter(
                        array_map('trim', explode(',', strval($field['value'])))
                    );

                    for ($i = 0; $i < count($tags); $i++) {
                        $data['bridge']['custom_fields'][] = [
                            'name' => "tags[{$i}]",
                            'value' => $tags[$i],
                        ];
                    }
                }

                array_splice($data['bridge']['custom_fields'], $index, 1);
            }
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Company Contacts', 'forms-bridge'),
    'description' => __(
        'Contact form for companies template. The resulting bridge will convert form submissions into new companies linked to contacts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Contacts', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/invoicing/v1/contacts',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'type',
            'label' => __('Contact type', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Unspecified', 'forms-bridge'),
                    'value' => '0',
                ],
                [
                    'label' => __('Client', 'forms-bridge'),
                    'value' => 'client',
                ],
                [
                    'label' => __('Lead', 'forms-bridge'),
                    'value' => 'lead',
                ],
                [
                    'label' => __('Supplier', 'forms-bridge'),
                    'value' => 'supplier',
                ],
                [
                    'label' => __('Debtor', 'forms-bridge'),
                    'value' => 'debtor',
                ],
                [
                    'label' => __('Creditor', 'forms-bridge'),
                    'value' => 'creditor',
                ],
            ],
            'required' => true,
            'default' => '0',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tags',
            'label' => __('Tags', 'forms-bridge'),
            'description' => __('Tags separated by commas', 'forms-bridge'),
            'type' => 'string',
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/invoicing/v1/contacts',
        'custom_fields' => [
            [
                'name' => 'defaults.language',
                'value' => '$locale',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'company_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'code',
                    'to' => 'vatnumber',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'address',
                    'to' => 'billAddress.address',
                    'cast' => 'string',
                ],
                [
                    'from' => 'postalCode',
                    'to' => 'billAddress.postalCode',
                    'cast' => 'string',
                ],
                [
                    'from' => 'city',
                    'to' => 'billAddress.city',
                    'cast' => 'string',
                ],
                [
                    'from' => 'contact_name',
                    'to' => 'contactPersons[0].name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'email',
                    'to' => 'contactPersons[0].email',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'phone',
                    'to' => 'contactPersons[0].phone',
                    'cast' => 'copy',
                ],
            ],
            [],
            [
                [
                    'from' => 'country',
                    'to' => 'country',
                    'cast' => 'null',
                ],
                [
                    'from' => 'country_code',
                    'to' => 'countryCode',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'countryCode',
                    'to' => 'billAddress.countryCode',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => [
            'skip-if-contact-exists',
            'iso2-country-code',
            'prefix-vatnumber',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Company', 'forms-bridge'),
                'name' => 'company_name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Tax ID', 'forms-bridge'),
                'name' => 'code',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'address',
                'type' => 'text',
            ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'postalCode',
                'type' => 'text',
            ],
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
            ],
            [
                'label' => __('Country', 'forms-bridge'),
                'name' => 'country',
                'type' => 'options',
                'options' => array_map(function ($country_code) {
                    global $forms_bridge_iso2_countries;
                    return [
                        'value' => $country_code,
                        'label' => $forms_bridge_iso2_countries[$country_code],
                    ];
                }, array_keys($forms_bridge_iso2_countries)),
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
                'required' => true,
            ],
        ],
    ],
];
