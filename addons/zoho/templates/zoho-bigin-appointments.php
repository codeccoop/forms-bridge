<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_prune_empties',
    function ($prune, $bridge) {
        if ($bridge->template === 'zoho-bigin-appointments') {
            return true;
        }

        return $prune;
    },
    10,
    2
);

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'zoho-bigin-appointments') {
            return $payload;
        }

        $payload['Owner'] = [
            'id' => $payload['Owner'],
        ];

        $contact = [
            'First_Name' => $payload['First_Name'],
            'Last_Name' => $payload['Last_Name'],
            'Email' => $payload['Email'],
        ];

        $response = $bridge
            ->patch([
                'name' => 'zoho-bigin-appointment-contact',
                'endpoint' => '/bigin/v2/Contacts',
                'template' => null,
            ])
            ->submit($contact);

        if (is_wp_error($response)) {
            $data = json_decode(
                $response->get_error_data()['response']['body'],
                true
            );

            if ($data['data'][0]['code'] !== 'DUPLICATE_DATA') {
                do_action(
                    'forms_bridge_on_failure',
                    $bridge,
                    $response,
                    $payload
                );

                return;
            }

            $contact_id = $data['data'][0]['details']['duplicate_record']['id'];
        } else {
            $contact_id = $response['data'][0]['details']['id'];
        }

        $payload['Participants'] = [
            [
                'type' => 'contact',
                'participant' => $contact_id,
            ],
        ];

        foreach (array_keys($contact) as $field) {
            unset($payload[$field]);
        }

        $date = $payload['date'];
        $hour = $payload['h'];
        $minute = $payload['m'];

        $form_data = apply_filters('forms_bridge_form', null);
        $date_index = array_search(
            'date',
            array_column($form_data['fields'], 'name')
        );
        $date_format = $form_data['fields'][$date_index]['format'] ?? '';

        if (strstr($date_format, '-')) {
            $separator = '-';
        } elseif (strstr($date_format, '.')) {
            $separator = '.';
        } elseif (strstr($date_format, '/')) {
            $separator = '/';
        }

        switch (substr($date_format, 0, 1)) {
            case 'y':
                [$year, $month, $day] = explode($separator, $date);
                break;
            case 'm':
                [$month, $day, $year] = explode($separator, $date);
                break;
            case 'd':
                [$day, $month, $year] = explode($separator, $date);
                break;
        }

        $date = "{$year}-{$month}-{$day}";

        if (preg_match('/(am|pm)/i', $hour, $matches)) {
            $hour = (int) $hour;
            if (strtolower($matches[0]) === 'pm') {
                $hour += 12;
            }
        }

        $time = strtotime("{$date} {$hour}:{$minute}");

        if ($time === false) {
            do_action(
                'forms_bridge_on_failure',
                $bridge,
                new WP_Error('Invalid date format'),
                $payload
            );

            return;
        }

        unset($payload['date']);
        unset($payload['h']);
        unset($payload['m']);

        $payload['Start_DateTime'] = date('c', $time);
        $payload['End_DateTime'] = date('c', $time + 3600);

        $payload['Remind_At'] = [
            [
                'unit' => 1,
                'period' => 'hours',
            ],
            [
                'unit' => 30,
                'period' => 'minutes',
            ],
        ];

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('Bigin Appointments', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#backend',
            'name' => 'name',
            'label' => __('Backend name', 'forms-bridge'),
            'type' => 'string',
            'default' => 'Zoho Bigin API',
        ],
        [
            'ref' => '#credential',
            'name' => 'organization_id',
            'label' => __('Organization ID', 'form-bridge'),
            'description' => __(
                'From your organization dashboard, expand the profile sidebar and click on the copy user ID icon to get your organization ID.',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#credential',
            'name' => 'client_id',
            'label' => __('Client ID', 'forms-bridge'),
            'description' => __(
                'You have to create a Self-Client Application on the Zoho Developer Console and get the Client ID',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#credential',
            'name' => 'client_secret',
            'label' => __('Client Secret', 'forms-bridge'),
            'description' => __(
                'You have to create a Self-Client Application on the Zoho Developer Console and get the Client Secret',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Endpoint', 'forms-bridge'),
            'type' => 'string',
            'value' => '/bigin/v2/Events',
        ],
        [
            'ref' => '#bridge',
            'name' => 'scope',
            'label' => __('Scope', 'forms-bridge'),
            'type' => 'string',
            'value' =>
                'ZohoBigin.modules.contacts.CREATE,ZohoBigin.modules.events.CREATE',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Appointments', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'Owner',
            'label' => __('Owner ID', 'forms-bridge'),
            'descritpion' => __(
                'ID of the owner user of the event',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'Event_Title',
            'label' => __('Event title', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => __('Web Appointment', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'All_day',
            'label' => __('Is all day event?', 'forms-bridge'),
            'type' => 'boolean',
            'default' => false,
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'Owner',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'Event_Title',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'All_day',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'First_Name',
                'label' => __('First name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Last_Name',
                'label' => __('Last name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'date',
                'label' => __('Date', 'forms-bridge'),
                'type' => 'date',
                'required' => true,
            ],
            [
                'name' => 'h',
                'label' => __('Hour', 'forms-bridge'),
                'type' => 'options',
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
                'required' => 'true',
            ],
            [
                'name' => 'm',
                'label' => __('Minute', 'forms-bridge'),
                'type' => 'options',
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
                'required' => true,
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/bigin/v2/Events',
        'scope' =>
            'ZohoBigin.modules.contacts.CREATE,ZohoBigin.modules.events.CREATE',
        'mappers' => [
            [
                'from' => 'Owner',
                'to' => 'Owner',
                'cast' => 'string',
            ],
        ],
    ],
    'backend' => [
        'base_url' => 'https://www.zohoapis.com',
        'headers' => [
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
];
