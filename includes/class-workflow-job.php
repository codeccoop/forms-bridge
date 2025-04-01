<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_workflow_noop_method($payload)
{
    return $payload;
}

class Workflow_Job
{
    private static $schema = [
        'title' => ['type' => 'string'],
        'description' => ['type' => 'string'],
        'method' => ['type' => 'string'],
        'input' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'required' => ['type' => 'boolean'],
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'enum' => [
                                    'string',
                                    'integer',
                                    'number',
                                    'array',
                                    'object',
                                    'boolean',
                                    'null',
                                ],
                            ],
                            'items' => [
                                'type' => ['array', 'object'],
                                'additionalProperties' => true,
                                'additionalItems' => true,
                            ],
                            'properties' => [
                                'type' => 'object',
                                'additionalProperties' => true,
                            ],
                            'maxItems' => ['type' => 'integer'],
                            'minItems' => ['type' => 'integer'],
                            'additionalProperties' => ['type' => 'boolean'],
                            'additionalItems' => ['type' => 'boolean'],
                        ],
                        'required' => ['type'],
                        'additionalProperties' => false,
                    ],
                ],
                'required' => ['name', 'schema'],
                'additionalProperties' => false,
            ],
        ],
        'output' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'touch' => ['type' => 'boolean'],
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'enum' => [
                                    'string',
                                    'integer',
                                    'number',
                                    'array',
                                    'object',
                                    'boolean',
                                    'null',
                                ],
                            ],
                            'items' => [
                                'type' => ['array', 'object'],
                                'additionalProperties' => true,
                                'additionalItems' => true,
                            ],
                            'properties' => [
                                'type' => 'object',
                                'additionalProperties' => true,
                            ],
                            'maxItems' => ['type' => 'integer'],
                            'minItems' => ['type' => 'integer'],
                            'additionalProperties' => ['type' => 'boolean'],
                            'additionalItems' => ['type' => 'boolean'],
                        ],
                        'required' => ['type'],
                        'additionalProperties' => false,
                    ],
                ],
                'required' => ['name', 'schema'],
                'additionalProperties' => false,
            ],
        ],
        'submission_callbacks' => [
            'type' => 'object',
            'properties' => [
                'before' => ['type' => 'string'],
                'after' => ['type' => 'string'],
            ],
            'additionalProperties' => false,
        ],
    ];

    protected $api;
    private $file;
    private $name;
    private $config;
    private $next = null;

    public static function from_workflow($workflow)
    {
        $workflow = array_reverse($workflow);
        return self::workflow_chain($workflow);
    }

    private static function workflow_chain($workflow, $next = null)
    {
        if (empty($workflow)) {
            return $next;
        }

        $name = array_shift($workflow);
        $job = apply_filters('forms_bridge_workflow_job', null, $name);

        if (empty($job)) {
            return;
        }

        $job = clone $job;

        if ($next) {
            $job->chain($next);
        }

        return self::workflow_chain($workflow, $job);
    }

    public function __construct($name, $config, $api)
    {
        $this->api = $api;
        $this->name = $api . '-' . $name;
        $this->config = $this->validate_config($config);

        add_filter(
            'forms_bridge_workflow_jobs',
            function ($jobs, $api = null) {
                if ($api && $api !== $this->api) {
                    return $jobs;
                }

                if (!wp_is_numeric_array($jobs)) {
                    $jobs = [];
                }

                if (is_wp_error($this->config)) {
                    return $jobs;
                }

                $jobs[] = $this;
                return $jobs;
            },
            10,
            2
        );

        add_filter(
            'forms_bridge_workflow_job',
            function ($job, $name, $api = null) {
                if ($job instanceof Workflow_Job) {
                    return $job;
                }

                if ($api && $api !== $this->api) {
                    return $job;
                }

                if (is_wp_error($this->config)) {
                    return $job;
                }

                if ($name === $this->name) {
                    return $this;
                }
            },
            10,
            3
        );
    }

    public function __get($name)
    {
        switch ($name) {
            case 'api':
                return $this->api;
            case 'name':
                return $this->name;
            case 'file':
                return $this->file;
            case 'next':
                return $this->next;
            case 'config':
                return $this->config;
            default:
                return $this->config[$name] ?? null;
        }
    }

    public function chain($job)
    {
        $this->next = $job;
    }

    public function run($payload, $bridge, $mutations = null)
    {
        $original = $payload;

        if ($mutations === null) {
            $mutations = array_slice($bridge->mutations, 1);
        }

        if ($this->missing_requireds($payload)) {
            if ($next_job = $this->next) {
                $mutations = array_slice($mutations, 1);
                $payload = $next_job->run($payload, $bridge, $mutations);
            }

            return $payload;
        }

        $payload = apply_filters(
            'forms_bridge_workflow_job_payload',
            $payload,
            $this,
            $bridge
        );

        $method = $this->method;
        $payload = $method($payload, $bridge);

        if (empty($payload)) {
            return;
        } elseif (is_wp_error($payload)) {
            $error = $payload;
            do_action('forms_bridge_on_failure', $bridge, $error, $original);
            return;
        }

        $payload = $this->output_payload($payload);

        $mutation = array_shift($mutations) ?: [];
        $payload = $bridge->apply_mutation($payload, $mutation);

        if ($next_job = $this->next) {
            $payload = $next_job->run($payload, $bridge, $mutations);
        }

        if (isset($this->submission_callbacks['before'])) {
            add_action(
                'forms_bridge_before_submission',
                [$this, 'before_submission'],
                10,
                3
            );
        }

        if (isset($this->submission_callbacks['after'])) {
            add_action(
                'forms_bridge_after_submission',
                [$this, 'after_submission'],
                10,
                4
            );
        }

        return $payload;
    }

    public function before_submission($bridge, $payload, $attachments = [])
    {
        remove_action(
            'forms_bridge_before_submission',
            [$this, 'before_submission'],
            10,
            3
        );

        $callback = $this->submission_callbacks['before'];
        $callback($bridge, $payload, $attachments);
    }

    public function after_submission(
        $bridge,
        $response,
        $payload,
        $attachments = []
    ) {
        remove_action(
            'forms_bridge_after_submission',
            [$this, 'after_submission'],
            10,
            4
        );

        $callback = $this->submission_callback['after'];
        $callback($bridge, $response, $payload, $attachments);
    }

    public function to_json()
    {
        return [
            'name' => $this->name,
            'title' => $this->config['title'],
            'description' => $this->config['description'],
            'input' => $this->config['input'],
            'output' => $this->config['output'],
        ];
    }

    private function validate_config($data)
    {
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => static::$schema,
            'required' => array_keys(static::$schema),
        ];

        $data = array_merge(
            [
                'submission_callbacks' => [],
                'method' => '\FORMS_BRIDGE\forms_bridge_workflow_noop_method',
            ],
            $data
        );

        $is_valid = rest_validate_value_from_schema($data, $schema);
        if (is_wp_error($is_valid)) {
            return $is_valid;
        }

        $data = rest_sanitize_value_from_schema($data, $schema);

        if (!function_exists($data['method'])) {
            return new WP_Error(
                'method_is_not_function',
                __('Job method is not a function', 'forms-bridge'),
                $data
            );
        }

        foreach ($data['submission_callbacks'] as $callback) {
            if (!function_exists($callback)) {
                return new WP_Error(
                    'method_is_not_function',
                    __('Job callback is not a function', 'forms-bridge'),
                    $data
                );
            }
        }

        return $data;
    }

    private function missing_requireds($payload)
    {
        $requireds = array_filter($this->input, function ($input_field) {
            return $input_field['required'] ?? false;
        });

        foreach ($requireds as $required) {
            if (!isset($payload[$required['name']])) {
                return true;
            }
        }

        return false;
    }

    private function output_payload($payload)
    {
        foreach ($this->input as $input_field) {
            $persist = false;
            foreach ($this->output as $output_field) {
                if ($input_field['name'] === $output_field['name']) {
                    $persist = true;
                    break;
                }
            }

            if (!$persist) {
                unset($payload[$input_field['name']]);
            }
        }

        return $payload;
    }
}

$jobs_dir = dirname(__FILE__) . '/workflow-jobs';
foreach (array_diff(scandir($jobs_dir), ['.', '..']) as $file) {
    require_once $jobs_dir . '/' . $file;
}
