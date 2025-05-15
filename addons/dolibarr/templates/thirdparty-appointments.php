<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Company Appointments', 'forms-bridge'),
    'description' => __(
        'Appointments form template. The resulting bridge will convert form submissions into events on the calendar linked to new companies.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/index.php/agendaevents',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'userownerid',
            'label' => __('Owner', 'forms-bridge'),
            'description' => __('Host user of the event', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'type_code',
            'label' => __('Event type', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Meeting', 'forms-bridge'),
                    'value' => 'AC_RDV',
                ],
                [
                    'label' => __('Phone call', 'forms-bridge'),
                    'value' => 'AC_TEL',
                ],
                [
                    'label' => __('Intervention on site', 'forms-bridge'),
                    'value' => 'AC_INT',
                ],
                [
                    'label' => __('Other', 'forms-bridge'),
                    'value' => 'AC_OTH',
                ],
            ],
            'default' => 'AC_RDV',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'label',
            'label' => __('Event label', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => __('Web appointment', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'fulldayevent',
            'label' => __('Is all day event?', 'forms-bridge'),
            'type' => 'boolean',
            'default' => false,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'duration',
            'label' => __('Duration (Hours)', 'forms-bridge'),
            'type' => 'number',
            'default' => 1,
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Web Appointments', 'forms-bridge'),
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'company_name',
                'label' => __('Company name', 'forms-bridge'),
                'type' => 'text',
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
                'name' => 'contact_email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'poste',
                'label' => __('Job position', 'forms-bridge'),
                'type' => 'text',
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
    'bridge' => [
        'endpoint' => '/api/index.php/agendaevents',
        'mutations' => [
            [
                [
                    'from' => 'company_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'duration',
                    'to' => 'duration',
                    'cast' => 'number',
                ],
            ],
            [
                [
                    'from' => 'datetime',
                    'to' => 'date',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'contact_email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
            ],
            [],
        ],
        'workflow' => [
            'forms-bridge-date-fields-to-date',
            'dolibarr-appointment-dates',
            'dolibarr-contact-socid',
            'dolibarr-appointment-attendee',
        ],
    ],
];
