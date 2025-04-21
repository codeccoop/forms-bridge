<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'holded-appointments') {
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
    'title' => __('Appointments', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Appointments', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/crm/v1/events',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'event_name',
            'label' => __('Event name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => 'Web appointment',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'kind',
            'label' => __('Event type', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'value' => 'meeting',
                    'label' => __('Meeting', 'forms-bridge'),
                ],
                [
                    'value' => 'call',
                    'label' => __('Call', 'forms-bridge'),
                ],
                [
                    'value' => 'lunch',
                    'label' => __('Lunch', 'forms-bridge'),
                ],
                [
                    'value' => 'dinner',
                    'label' => __('Dinner', 'forms-bridge'),
                ],
            ],
            'required' => true,
            'default' => 'meeting',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'duration',
            'label' => __('Duration (Hours)', 'forms-bridge'),
            'type' => 'number',
            'default' => 1,
            'required' => true,
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
        'endpoint' => '/api/crm/v1/events',
        'custom_fields' => [
            [
                'name' => 'isperson',
                'value' => '1',
            ],
            [
                'name' => 'defaults.language',
                'value' => '$locale',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'isperson',
                    'to' => 'isperson',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'code',
                    'to' => 'vatnumber',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'your-name',
                    'to' => 'name',
                    'cast' => 'string',
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
            ],
            [
                [
                    'from' => 'datetime',
                    'to' => 'date',
                    'cast' => 'string',
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
            [
                [
                    'from' => 'event_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => [
            'forms-bridge-date-fields-to-date',
            'holded-appointment-dates',
            'forms-bridge-iso2-country-code',
            'holded-prefix-vatnumber',
            'holded-bind-contact',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'your-name',
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
                'name' => 'date',
                'label' => __('Date', 'forms-bridge'),
                'type' => 'date',
                'required' => true,
            ],
            [
                'name' => 'hour',
                'label' => __('Hour', 'forms-bridge'),
                'type' => 'options',
                'required' => true,
                'options' => [
                    [
                        'label' => __('1 AM', 'forms-bridge'),
                        'value' => '01',
                    ],
                    [
                        'label' => __('2 AM', 'forms-bridge'),
                        'value' => '02',
                    ],
                    [
                        'label' => __('3 AM', 'forms-bridge'),
                        'value' => '03',
                    ],
                    [
                        'label' => __('4 AM', 'forms-bridge'),
                        'value' => '04',
                    ],
                    [
                        'label' => __('5 AM', 'forms-bridge'),
                        'value' => '05',
                    ],
                    [
                        'label' => __('6 AM', 'forms-bridge'),
                        'value' => '06',
                    ],
                    [
                        'label' => __('7 AM', 'forms-bridge'),
                        'value' => '07',
                    ],
                    [
                        'label' => __('8 AM', 'forms-bridge'),
                        'value' => '08',
                    ],
                    [
                        'label' => __('9 AM', 'forms-bridge'),
                        'value' => '09',
                    ],
                    [
                        'label' => __('10 AM', 'forms-bridge'),
                        'value' => '10',
                    ],
                    [
                        'label' => __('11 AM', 'forms-bridge'),
                        'value' => '11',
                    ],
                    [
                        'label' => __('12 AM', 'forms-bridge'),
                        'value' => '12',
                    ],
                    [
                        'label' => __('1 PM', 'forms-bridge'),
                        'value' => '13',
                    ],
                    [
                        'label' => __('2 PM', 'forms-bridge'),
                        'value' => '14',
                    ],
                    [
                        'label' => __('3 PM', 'forms-bridge'),
                        'value' => '15',
                    ],
                    [
                        'label' => __('4 PM', 'forms-bridge'),
                        'value' => '16',
                    ],
                    [
                        'label' => __('5 PM', 'forms-bridge'),
                        'value' => '17',
                    ],
                    [
                        'label' => __('6 PM', 'forms-bridge'),
                        'value' => '18',
                    ],
                    [
                        'label' => __('7 PM', 'forms-bridge'),
                        'value' => '19',
                    ],
                    [
                        'label' => __('8 PM', 'forms-bridge'),
                        'value' => '20',
                    ],
                    [
                        'label' => __('9 PM', 'forms-bridge'),
                        'value' => '21',
                    ],
                    [
                        'label' => __('10 PM', 'forms-bridge'),
                        'value' => '22',
                    ],
                    [
                        'label' => __('11 PM', 'forms-bridge'),
                        'value' => '23',
                    ],
                    [
                        'label' => __('12 PM', 'forms-bridge'),
                        'value' => '24',
                    ],
                ],
            ],
            [
                'name' => 'minute',
                'label' => __('Minute', 'forms-bridge'),
                'type' => 'options',
                'required' => true,
                'options' => [
                    ['label' => '00', 'value' => '00.0'],
                    ['label' => '05', 'value' => '05'],
                    ['label' => '10', 'value' => '10'],
                    ['label' => '15', 'value' => '15'],
                    ['label' => '20', 'value' => '20'],
                    ['label' => '25', 'value' => '25'],
                    ['label' => '30', 'value' => '30'],
                    ['label' => '35', 'value' => '35'],
                    ['label' => '40', 'value' => '40'],
                    ['label' => '45', 'value' => '45'],
                    ['label' => '50', 'value' => '50'],
                    ['label' => '55', 'value' => '55'],
                ],
            ],
        ],
    ],
];
