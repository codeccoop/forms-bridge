<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_holded_appointment_dates($payload)
{
    $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $payload['date']);
    if ($datetime === false) {
        return new WP_Error(
            'invalid-date',
            __('Invalid date time value', 'forms-bridge')
        );
    }

    $timestamp = $datetime->getTimestamp();
    $payload['startDate'] = $timestamp;
    $payload['duration'] = floatval($payload['duration'] ?? 1);

    return $payload;
}

return [
    'title' => __('Appointment dates', 'forms-bridge'),
    'description' => __(
        'Sets appointment start time and duration from datetime and duration fields',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_holded_appointment_dates',
    'input' => [
        [
            'name' => 'date',
            'required' => true,
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'duration',
            'schema' => ['type' => 'number'],
        ],
    ],
    'output' => [
        [
            'name' => 'startDate',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'duration',
            'schema' => ['type' => 'number'],
        ],
    ],
];
