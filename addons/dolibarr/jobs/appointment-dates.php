<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_appointment_dates($payload, $bridge)
{
    $time = forms_bridge_dolibarr_date_to_time($payload, $bridge);

    if (is_wp_error($time)) {
        return $time;
    }

    $payload['datep'] = (string) $time;
    $payload['duration'] = floatval($payload['duration'] ?? 1);
    $payload['datef'] = $payload['duration'] * 3600 + $payload['datep'];

    return $payload;
}

return [
    'title' => __('Appointment dates', 'forms-bridge'),
    'description' => __(
        'Sets appointment start, end time and duration from "date", "hour", "minute" and "duration" fields.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_appointment_dates',
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
            'name' => 'datep',
            'type' => 'number',
        ],
        [
            'name' => 'datef',
            'type' => 'number',
        ],
        [
            'name' => 'duration',
            'type' => 'number',
        ],
    ],
];
