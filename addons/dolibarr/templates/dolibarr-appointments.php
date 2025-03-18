<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_prune_empties',
    function ($prune, $bridge) {
        if ($bridge->template === 'dolibarr-appointments') {
            return true;
        }

        return $prune;
    },
    9,
    2
);

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'dolibarr-appointments') {
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
        if ($bridge->template !== 'dolibarr-appointments') {
            return $payload;
        }

        $backend = $bridge->backend;

        $payload['owner'] = base64_decode($payload['owner']);

        $response = $backend->get('/api/index.php/users', [
            'limit' => '1',
            'sqlfilters' => "(t.email:=:'{$payload['owner']}')",
        ]);

        if (is_wp_error($response)) {
            do_action('forms_bridge_on_failure', $bridge, $response, $payload);
            return;
        }

        $payload['userownerid'] = $response['data'][0]['id'];
        unset($payload['owner']);

        $response = $backend->get('/api/index.php/contacts', [
            'limit' => '1',
            'sqlfilters' => "(t.firstname:like:'{$payload['firstname']}') and (t.lastname:like:'{$payload['lastname']}') and (t.email:=:'{$payload['email']}')",
        ]);

        if (is_wp_error($response)) {
            $error_data = $response->get_error_data();
            $response_code = $error_data['response']['response']['code'];

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

        if (is_wp_error($response)) {
            $name = "{$payload['firstname']} {$payload['lastname']}";
            $response = $backend->post('/api/index.php/contacts', [
                'name' => $name,
                'firstname' => $payload['firstname'],
                'lastname' => $payload['lastname'],
                'email' => $payload['email'],
            ]);

            if (is_wp_error($response)) {
                do_action(
                    'forms_bridge_on_failure',
                    $bridge,
                    $response,
                    $payload
                );

                return;
            }

            $contact_id = $response['body'];
        } else {
            $contact_id = $response['data'][0]['id'];
        }

        $payload['socpeopleassigned'] = [
            $contact_id => [
                'id' => $contact_id,
                'mandatory' => '0',
                'answer_status' => '0',
                'transparency' => '0',
            ],
        ];

        unset($payload['firstname']);
        unset($payload['lastname']);
        unset($payload['email']);

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

        $payload['datep'] = (string) $time;

        $end = $payload['duration'] * 3600 + $time;
        $payload['datef'] = (string) $end;

        unset($payload['date']);
        unset($payload['h']);
        unset($payload['m']);

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('Appointments', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Endpoint', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'value' => '/api/index.php/agendaevents',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Web Appointments', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'owner',
            'label' => __('Owner email', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
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
            'default' => true,
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'label',
            'label' => __('Event label', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => __('Web Appointment', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'fulldayevent',
            'label' => __('Is all day event?', 'forms-bridge'),
            'type' => 'boolean',
            'default' => false,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'duration',
            'label' => __('Duration (Hours)', 'forms-bridge'),
            'type' => 'number',
            'default' => 1,
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'type_code',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'label',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'fulldayevent',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'owner',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'duration',
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
                'name' => 'date',
                'label' => __('Date', 'forms-bridge'),
                'type' => 'date',
                'required' => true,
            ],
            [
                'name' => 'h',
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
                'name' => 'm',
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
    'backend' => [
        'headers' => [
            'name' => 'Accept',
            'value' => 'application/json',
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/agendaevents',
        'method' => 'POST',
        'mappers' => [
            [
                'from' => 'type_code',
                'to' => 'type_code',
                'cast' => 'string',
            ],
            [
                'from' => 'fulldayevent',
                'to' => 'fulldayevent',
                'cast' => 'string',
            ],
            [
                'from' => 'userownerid',
                'to' => 'userownerid',
                'cast' => 'string',
            ],
            [
                'from' => 'duration',
                'to' => 'duration',
                'cast' => 'float',
            ],
        ],
    ],
];
