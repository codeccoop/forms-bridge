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

        $jobs[] = new \FORMS_BRIDGE\Workflow_Job(
            'country-code',
            [
                'title' => __('Country code', 'forms-bridge'),
                'description' => __(
                    'Gets the ISO2 country code from country names and replace its value',
                    'forms-bridge'
                ),
                'method' => 'forms_bridge_workflow_job_country_code',
                'input' => [
                    [
                        'name' => 'country',
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
                'output' => [
                    [
                        'name' => 'country_code',
                        'type' => 'string',
                    ],
                ],
            ],
            'forms-bridge'
        );

        return $jobs;
    },
    20,
    2
);

function forms_bridge_workflow_job_country_code($payload)
{
    global $forms_bridge_country_codes;
    $country_code = strtoupper($payload['country']);

    if (!isset($forms_bridge_country_codes[$country_code])) {
        $countries_by_name = array_reduce(
            array_keys($forms_bridge_country_codes),
            function ($labels, $country_code) {
                global $forms_bridge_country_codes;
                $label = $forms_bridge_country_codes[$country_code];
                $labels[$label] = $country_code;
                return $labels;
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
