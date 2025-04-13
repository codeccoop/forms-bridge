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
            'iso3-country-code',
            [
                'title' => __('ISO3 country code', 'forms-bridge'),
                'description' => __(
                    'Gets the ISO3 country code from country names and replace its value',
                    'forms-bridge'
                ),
                'method' => 'forms_bridge_workflow_job_iso3_country_code',
                'input' => [
                    [
                        'name' => 'country',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                    ],
                ],
                'output' => [
                    [
                        'name' => 'country',
                        'schema' => ['type' => 'string'],
                    ],
                    [
                        'name' => 'country_code',
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

function forms_bridge_workflow_job_iso3_country_code($payload)
{
    global $forms_bridge_iso3_countries;
    $country_code = strtoupper($payload['country']);

    if (!isset($forms_bridge_iso3_countries[$country_code])) {
        $countries_by_name = array_reduce(
            array_keys($forms_bridge_iso3_countries),
            function ($countries, $country_code) {
                global $forms_bridge_iso3_countries;
                $country = $forms_bridge_iso3_countries[$country_code];
                $countries[$country] = $country_code;
                return $countries;
            },
            []
        );

        if (isset($countries_by_name[$payload['country']])) {
            $payload['country_code'] = $countries_by_name[$payload['country']];
        }
    } else {
        $payload['country_code'] = $country_code;
    }

    return $payload;
}
