<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_zoho_appointment_dates($payload)
{
    $time = forms_bridge_zoho_date_to_time($payload);

    if (is_wp_error($time)) {
        return $time;
    }

    $duration = $payload['duration'] ?? 1;

    $payload['Start_DateTime'] = date('c', $time);
    $payload['End_DateTime'] = date('c', $time + 3600 * $duration);

    return $payload;
}

return [
    'title' => __('Appointment dates', 'forms-bridge'),
    'description' => __('', 'forms-bridge'),
    'method' => 'forms_bridge_zoho_appointment_dates',
    'input' => [
        [
            'name' => 'date',
            'required' => true,
            'type' => 'string',
        ],
        [
            'name' => 'hour',
            'type' => 'string',
        ],
        [
            'name' => 'minute',
            'type' => 'string',
        ],
        [
            'name' => 'duration',
            'type' => 'number',
        ],
    ],
    'output' => [
        [
            'name' => 'Start_DateTime',
            'type' => 'string',
        ],
        [
            'name' => 'End_DateTime',
            'type' => 'string',
        ],
    ],
];
