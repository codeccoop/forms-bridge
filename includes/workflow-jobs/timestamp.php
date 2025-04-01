<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_workflow_jobs',
    function ($jobs) {
        if (!wp_is_numeric_array($jobs)) {
            $jobs = [];
        }

        $job = new \FORMS_BRIDGE\Workflow_Job(
            'timestamp',
            [
                'title' => __('Timestamp', 'forms-bridge'),
                'description' => __(
                    'Gets date, hour and minute inputs and transform its values to a timestamp',
                    'forms-bridge'
                ),
                'method' => 'forms_bridge_workflow_job_timestamp',
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
                        'name' => 'timestamp',
                        'schema' => ['type' => 'integer'],
                    ],
                ],
            ],
            'forms-bridge'
        );

        if (!is_wp_error($job->config)) {
            $jobs[] = $job;
        }

        return $jobs;
    },
    20,
    1
);

function forms_bridge_workflow_job_timestamp($payload)
{
    $date = $payload['date'];
    $hour = $payload['hour'] ?? '00';
    $minute = $payload['minute'] ?? '00';

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
        return new WP_Error('Invalid date format');
    }

    $payload['timestamp'] = $time;
    return $payload;
}
