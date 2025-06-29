<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'name' => 'date-fields-to-date',
    'title' => __('Format date fields', 'forms-bridge'),
    'description' => __(
        'Gets date, hour and minute fields and merge its values into a date with format Y-m-d H:M:S',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_workflow_job_format_date_fields',
    'input' => [
        [
            'name' => 'date',
            'required' => true,
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'hour',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'minute',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [
        [
            'name' => 'datetime',
            'schema' => ['type' => 'string'],
        ],
    ],
];

function forms_bridge_workflow_job_format_date_fields($payload)
{
    $date = $payload['date'];
    $hour = $payload['hour'] ?? '00';
    $minute = $payload['minute'] ?? '00';

    $form_data = FBAPI::get_current_form();
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
        return new WP_Error(
            'invalid-date',
            __('Invalid date format', 'forms-bridge')
        );
    }

    $payload['datetime'] = date('Y-m-d H:i:s', $time);
    return $payload;
}
