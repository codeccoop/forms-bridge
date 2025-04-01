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
            'current-locale',
            [
                'title' => __('Current locale', 'forms-bridge'),
                'description' => __(
                    'Adds the current locale to the payload',
                    'forms-bridge'
                ),
                'method' => 'forms_bridge_workflow_job_current_locale',
                'input' => [],
                'output' => [
                    [
                        'name' => 'locale',
                        'schema' => ['type' => 'string'],
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

function forms_bridge_workflow_job_current_locale($payload)
{
    $payload['locale'] = $payload['locale'] ?? get_locale();
    return $payload;
}
