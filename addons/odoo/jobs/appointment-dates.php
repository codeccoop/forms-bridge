<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_appointment_dates($payload)
{
    $duration = $payload['duration'] ?? 1;

    $payload['start'] = date('Y-m-d H:i:s', $payload['timestamp']);

    $end = $duration * 3600 + $payload['timestamp'];
    $payload['stop'] = date('Y-m-d H:i:s', $end);

    return $payload;
}

return [
    'title' => __('Appointment dates', 'forms-bridge'),
    'description' => __(
        'Sets appointment start and stop time from "timestamp" and "duration" fields.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_appointment_dates',
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
            'name' => 'start',
            'type' => 'string',
        ],
        [
            'name' => 'stop',
            'type' => 'string',
        ],
    ],
];
