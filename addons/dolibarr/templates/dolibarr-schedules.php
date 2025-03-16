<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_prune_empties',
    function ($prune, $bridge) {
        if ($bridge->template === 'dolibarr-schedules') {
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
        if ($template_name === 'dolibarr-schedules') {
            $index = array_search(
                'date',
                array_column($data['form']['fields'], 'name')
            );

            $field = &$data['form']['fields'][$index];
            $field['min'] = date('Y-m-d', time());
        }

        return $data;
    },
    10,
    2
);

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'dolibarr-schedules') {
            return $payload;
        }

        $backend = $bridge->backend;
        $response = $backend->get('/api/index.php/contacts', [
            'sortfield' => 't.rowid',
            'sortorder' => 'ASC',
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

        unset($payload['date']);
        unset($payload['h']);
        unset($payload['m']);

        $time = strtotime("{$date} {$hour}:{$minute}");
        $payload['datep'] = (string) $time;
        $payload['datef'] = (string) $time;

        return $payload;
    },
    10,
    2
);

return [
    'title' => __('Dolibarr Schedules', 'forms-bridge'),
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
            'default' => __('Schedules', 'forms-bridge'),
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
            'name' => 'location',
            'label' => __('Location (optional)', 'forms-bridge'),
            'type' => 'string',
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'userownerid',
            'label' => __('Owen user ID (optional)', 'forms-bridge'),
            'type' => 'number',
        ],
    ],
    'form' => [
        'title' => __('Schedules', 'forms-bridge'),
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
                'name' => 'location',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'userownerid',
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
                    ['label' => '01', 'value' => '01'],
                    ['label' => '02', 'value' => '02'],
                    ['label' => '03', 'value' => '03'],
                    ['label' => '04', 'value' => '04'],
                    ['label' => '05', 'value' => '05'],
                    ['label' => '06', 'value' => '06'],
                    ['label' => '07', 'value' => '07'],
                    ['label' => '08', 'value' => '08'],
                    ['label' => '09', 'value' => '09'],
                    ['label' => '10', 'value' => '10'],
                    ['label' => '11', 'value' => '11'],
                    ['label' => '12', 'value' => '12'],
                    ['label' => '13', 'value' => '13'],
                    ['label' => '14', 'value' => '14'],
                    ['label' => '15', 'value' => '15'],
                    ['label' => '16', 'value' => '16'],
                    ['label' => '17', 'value' => '17'],
                    ['label' => '18', 'value' => '18'],
                    ['label' => '19', 'value' => '19'],
                    ['label' => '20', 'value' => '20'],
                    ['label' => '21', 'value' => '21'],
                    ['label' => '22', 'value' => '22'],
                    ['label' => '23', 'value' => '23'],
                    ['label' => '24', 'value' => '24'],
                    ['label' => '25', 'value' => '25'],
                    ['label' => '26', 'value' => '26'],
                    ['label' => '27', 'value' => '27'],
                    ['label' => '28', 'value' => '28'],
                    ['label' => '29', 'value' => '29'],
                    ['label' => '30', 'value' => '30'],
                    ['label' => '31', 'value' => '31'],
                    ['label' => '32', 'value' => '32'],
                    ['label' => '33', 'value' => '33'],
                    ['label' => '34', 'value' => '34'],
                    ['label' => '35', 'value' => '35'],
                    ['label' => '36', 'value' => '36'],
                    ['label' => '37', 'value' => '37'],
                    ['label' => '38', 'value' => '38'],
                    ['label' => '39', 'value' => '39'],
                    ['label' => '40', 'value' => '40'],
                    ['label' => '41', 'value' => '41'],
                    ['label' => '42', 'value' => '42'],
                    ['label' => '43', 'value' => '43'],
                    ['label' => '44', 'value' => '44'],
                    ['label' => '45', 'value' => '45'],
                    ['label' => '46', 'value' => '46'],
                    ['label' => '47', 'value' => '47'],
                    ['label' => '48', 'value' => '48'],
                    ['label' => '49', 'value' => '49'],
                    ['label' => '50', 'value' => '50'],
                    ['label' => '51', 'value' => '51'],
                    ['label' => '52', 'value' => '52'],
                    ['label' => '53', 'value' => '53'],
                    ['label' => '54', 'value' => '54'],
                    ['label' => '55', 'value' => '55'],
                    ['label' => '56', 'value' => '56'],
                    ['label' => '57', 'value' => '57'],
                    ['label' => '58', 'value' => '58'],
                    ['label' => '59', 'value' => '59'],
                    ['label' => '60', 'value' => '60'],
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
        ],
    ],
];
