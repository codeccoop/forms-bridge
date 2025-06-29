<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Appointments', 'forms-bridge'),
    'description' => __(
        'Appointments form template. The resulting bridge will convert form submissions into events on the calendar linked to new contacts.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Appointments', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'calendar.event',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'user_id',
            'label' => __('Owner email', 'forms-bridge'),
            'description' => __(
                'Email of the owner user of the appointment',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'event_name',
            'label' => __('Appointment name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => __('Web Appointment', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'allday',
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
    ],
    'bridge' => [
        'endpoint' => 'calendar.event',
        'mutations' => [
            [
                [
                    'from' => 'user_id',
                    'to' => 'user_id',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'your-name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'allday',
                    'to' => 'allday',
                    'cast' => 'boolean',
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
            [],
            [
                [
                    'from' => 'event_name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => [
            'date-fields-to-date',
            'appointment-dates',
            'appointment-attendee',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'your-name',
                'label' => __('Your name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => __('Your phone', 'forms-bridge'),
                'type' => 'text',
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
