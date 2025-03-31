<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_appointment_dates($payload)
{
    $payload['datep'] = (string) $payload['timestamp'];
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
            'name' => 'timestamp',
            'required' => true,
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
