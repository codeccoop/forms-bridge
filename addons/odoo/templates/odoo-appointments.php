<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'odoo-appointments') {
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
        if ($bridge->template !== 'odoo-appointments') {
            return $payload;
        }

        $payload['owner'] = base64_decode($payload['owner']);

        add_filter(
            'forms_bridge_rpc_payload',
            function ($payload, $bridge) {
                if ($bridge->name !== 'odoo-rpc-search-owner-by-email') {
                    return $payload;
                }

                $payload['params']['args'][] = ['commercial_partner_id'];
                return $payload;
            },
            10,
            2
        );

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-owner-by-email',
                'template' => null,
                'model' => 'res.users',
                'method' => 'search_read',
            ])
            ->submit([['email', '=', $payload['owner']]]);

        if (is_wp_error($response)) {
            do_action('forms_bridge_on_failure', $bridge, $response, $payload);
            return;
        }

        $owner_id = $response['data']['result'][0]['commercial_partner_id'][0];

        $response = $bridge
            ->patch([
                'name' => 'odoo-rpc-search-partner-by-email',
                'template' => null,
                'model' => 'res.partner',
                'method' => 'search',
            ])
            ->submit([['email', '=', $payload['email']]]);

        if (is_wp_error($response)) {
            $contact = [
                'name' => $payload['contact_name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? '',
            ];

            $response = $bridge
                ->patch([
                    'name' => 'odoo-rpc-create-appointment-contact',
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

            $partner_id = $response['data']['result'];
        } else {
            $partner_id = $response['data']['result'][0];
        }

        $payload['partner_ids'] = [$owner_id, $partner_id];

        unset($payload['owner']);
        unset($payload['contact_name']);
        unset($payload['email']);
        unset($payload['phone']);

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

        $payload['start'] = date('Y-m-d H:i:s', $time);

        $end = $payload['duration'] * 3600 + $time;
        $payload['stop'] = date('Y-m-d H:i:s', $end);

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
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Appointments', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'owner',
            'label' => __('Owner email', 'forms-bridge'),
            'description' => __(
                'Email of the owner user of the appointment',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'name',
            'label' => __('Appointment name', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
            'default' => __('Web Appointment', 'forms-bridge'),
        ],
        [
            'ref' => '#form/fields[]',
            'name' => 'allday',
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
    'bridge' => [
        'model' => 'calendar.event',
        'mappers' => [
            [
                'from' => 'allday',
                'to' => 'allday',
                'cast' => 'boolean',
            ],
            [
                'from' => 'duration',
                'to' => 'duration',
                'cast' => 'float',
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
                'name' => 'allday',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'duration',
                'type' => 'hidden',
                'required' => true,
            ],
            [
                'name' => 'contact_name',
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
];
