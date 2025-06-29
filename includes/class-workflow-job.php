<?php

namespace FORMS_BRIDGE;

use ParseError;
use Error;
use ReflectionFunction;
use WP_Error;
use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Noop function definition as a placeholder for workflow config defaults.
 *
 * @param array Workflow job payload.
 *
 * @return array
 */
function forms_bridge_workflow_noop_method($payload)
{
    return $payload;
}

/**
 * Workflow Job class
 */
class Workflow_Job
{
    /**
     * Handles the workflow job post type name.
     *
     * @var string
     */
    public const post_type = 'fb-workflow-job';

    /**
     * Handles the workflow job api space.
     *
     * @var string
     */
    protected $api;

    /**
     * Handles the workflow job ID. This should be unique and is the result of the concatenation
     * of the api slug and the job name.
     *
     * @var string
     */
    private $id;

    /**
     * Handles the workflow job config data. The data is validated before it's stored. If the validation
     * fails, the value is a WP_Error instance.
     *
     * @var array|WP_Error
     */
    private $config;

    /**
     * Pointer to the next workflow job on the workflow chain.
     *
     * @var Workflow_Job|null
     */
    private $next = null;

    /**
     * Workflow job's config schema public getter.
     *
     * @return array
     */
    public static function schema()
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'name' => [
                    'description' => __(
                        'Internal name of the job',
                        'forms-bridge'
                    ),
                    'type' => 'string',
                ],
                'title' => [
                    'description' => __(
                        'Public title of the job',
                        'forms-bridge'
                    ),
                    'type' => 'string',
                ],
                'description' => [
                    'description' => __(
                        'Short description of the job effects',
                        'forms-birdge'
                    ),
                    'type' => 'string',
                ],
                'method' => [
                    'description' => __(
                        'Name of the function with the job subroutine',
                        'forms-bridge'
                    ),
                    'type' => 'string',
                ],
                'input' => [
                    'description' => __(
                        'Input fields interface schema of the job',
                        'forms-bridge'
                    ),
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
                                    'additionalProperties' => [
                                        'type' => 'boolean',
                                    ],
                                    'additionalItems' => ['type' => 'boolean'],
                                    'required' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'additionalItems' => true,
                                    ],
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
                    'description' => __(
                        'Output fields interface schema of the job',
                        'forms-bridge'
                    ),
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'touch' => ['type' => 'boolean'],
                            'forward' => ['type' => 'boolean'],
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
                                    'additionalProperties' => [
                                        'type' => 'boolean',
                                    ],
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
                'snippet' => [
                    'description' => __(
                        'PHP code representation of the job subroutine',
                        'forms-bridge'
                    ),
                    'type' => 'string',
                ],
            ],
            'additionalProperties' => false,
            'required' => [
                'name',
                'title',
                'description',
                'input',
                'output',
                'method',
                'snippet',
            ],
        ];
    }

    /**
     * Enqueue the job instance as the last element of the workflow chain.
     *
     * @param string[] $workflow Array with workflow job names.
     * @param string $api Workflow API namespace.
     *
     * @return Workflow_Job $workflow Chain of workflow jobs.
     */
    public static function from_workflow($workflow, $api)
    {
        $workflow_jobs = [];
        $jobs = API::get_api_jobs($api);

        $i = count($workflow) - 1;
        while ($job_name = $workflow[$i] ?? null) {
            foreach ($jobs as $job) {
                if ($job->name === $job_name) {
                    $workflow_jobs[] = $job;
                }
            }
        }

        $next = null;
        foreach ($workflow_jobs as $job) {
            $job = clone $job;
            $job->chain($next);
            $next = $job;
        }

        return $next;
    }

    private static function reflect_method($method)
    {
        if (!function_exists($method)) {
            return '';
        }

        $reflection = new ReflectionFunction($method);

        $file = $reflection->getFileName();
        $from_line = $reflection->getStartLine();
        $to_line = $reflection->getEndLine();

        $snippet = implode(
            '',
            array_slice(file($file), $from_line - 1, $to_line - $from_line + 1)
        );

        if ($_snippet = strstr($snippet, '{')) {
            $snippet = substr($_snippet, 1);
        }

        $i = strlen($snippet);
        while (true) {
            $i--;

            if ($snippet[$i] === '}' || $i <= 0) {
                break;
            }
        }

        $snippet = substr($snippet, 0, $i);

        return trim($snippet);
    }

    private static function load_snippet($snippet, $name, $api)
    {
        try {
            $method_name = str_replace(
                '-',
                '_',
                'forms_bridge_job_' . $api . '_' . $name
            );

            $method =
                'if (!function_exists(\'' . $method_name . '\')) {' . "\n";
            $method .=
                'function ' . $method_name . '($payload, $bridge) {' . "\n";
            $method .= $snippet . "\n";
            $method .= 'return $payload;' . "\n";
            $method .= "}\n}\n";

            eval($method);
            return $method_name;
        } catch (ParseError $e) {
            Logger::log(
                "Syntax error on {$id} workflow job snippet",
                Logger::ERROR
            );
            Logger::log($e, Logger::ERROR);
        } catch (Error $e) {
            Logger::log(
                "Error while loading {$id} workflow job snippet",
                Logger::ERROR
            );
            Logger::log($e, Logger::ERROR);
        }
    }

    private static function config_from_post($post)
    {
        return [
            'name' => $post->post_name,
            'title' => $post->post_title,
            'description' => $post->post_excerpt,
            'input' => (array) get_post_meta(
                $post->ID,
                '_workflow-job-input',
                true
            ),
            'output' => (array) get_post_meta(
                $post->ID,
                '_workflow-job-output',
                true
            ),
            'snippet' => $post->post_content,
        ];
    }

    /**
     * Sets the job api and name attributes, validates the config data and enqueu themself to the workflow public
     * filter getters.
     *
     * @param string $name Job name.
     * @param array $config Job config.
     * @param string $api Api name.
     */
    public function __construct($config, $api)
    {
        if ($config instanceof WP_Post) {
            $config = self::config_from_post($config);
        }

        $this->api = $api;
        $this->config = $this->validate_config($config);

        if (!is_wp_error($this->config)) {
            $this->id = $api . '-' . $config['name'];

            add_filter(
                'forms_bridge_workflow_jobs',
                function ($jobs, $api = null) {
                    if ($api && $api !== $this->api) {
                        return $jobs;
                    }

                    if (!wp_is_numeric_array($jobs)) {
                        $jobs = [];
                    }

                    $jobs[] = $this;
                    return $jobs;
                },
                10,
                2
            );

            add_filter(
                'forms_bridge_workflow_job',
                function ($job, $name, $api) {
                    if ($job instanceof Workflow_Job) {
                        return $job;
                    }

                    $id = $api . '-' . $name;
                    if ($id !== $this->id) {
                        return $job;
                    }

                    return $this;
                },
                10,
                3
            );
        }
    }

    /**
     * Magic method to proxy private attributes.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'id':
                return $this->id;
            case 'api':
                return $this->api;
            case 'next':
                return $this->next;
            case 'config':
                return $this->config;
            default:
                return $this->config[$name] ?? null;
        }
    }

    /**
     * Sets the next job on the chain.
     *
     * @param Workflow_Job $job Next workflow job instance.
     */
    public function chain($job)
    {
        $this->next = $job;
    }

    /**
     * Gets the payload from the previous workflow stage and runs the job against it.
     *
     * @param array $payload Payload data.
     * @param Form_Bridge Workflow's bridge owner instance.
     * @param array $mutations Bridge's mutations.
     *
     * @return array|null Payload after job.
     */
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

        $method = $this->method;
        $payload = $method($payload, $bridge, $this);

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

        return $payload;
    }

    /**
     * Workflow job data serializer to be used on REST API response.
     *
     * @return array
     */
    public function to_json()
    {
        return [
            'id' => $this->id,
            'api' => $this->api,
            'name' => $this->name,
            'title' => $this->config['title'],
            'description' => $this->config['description'],
            'input' => $this->config['input'],
            'output' => $this->config['output'],
            'snippet' => $this->config['snippet'],
        ];
    }

    public function is_valid()
    {
        return !is_wp_error($this->config);
    }

    public function save()
    {
        if (!$this->is_valid()) {
            return $this->config;
        }

        $post_arr = [
            'post_name' => $this->name,
            'post_title' => $this->title,
            'post_excerpt' => $this->description,
            'post_content' => $this->snippet,
        ];

        $ids = get_posts([
            'post_type' => self::post_type,
            'name' => $this->name,
            'meta_key' => '_workflow-job-api',
            'meta_value' => $this->api,
            'fields' => 'ids',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'update_menu_item_cache' => false,
        ]);

        if (count($ids)) {
            $post_arr['ID'] = $ids[0];
        }

        $post_id = wp_update_post($post_arr, true);

        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, '_workflow-job-api', $this->api);
            update_post_meta($post_id, '_workflow-job-input', $this->input);
            update_post_meta($post_id, '_workflow-job-output', $this->output);
        }

        return $post_id;
    }

    /**
     * Vaildates the config data against the workflow job schema.
     *
     * @param array $data Workflow job config data.
     *
     * @return array|WP_Error Validation result.
     */
    private function validate_config($data)
    {
        $schema = self::schema();

        if (
            isset($data['name'], $data['snippet']) &&
            is_string($data['snippet'])
        ) {
            $data['method'] = self::load_snippet(
                $data['snippet'],
                $data['name'],
                $this->api
            );
        } else {
            if (isset($data['method']) && function_exists($data['method'])) {
                $data['snippet'] = self::reflect_method($data['method']);
            } else {
                $data['method'] =
                    '\FORMS_BRIDGE\forms_bridge_workflow_noop_method';
                $data['snippet'] = '';
            }
        }

        $data = wpct_plugin_validate_with_schema($data, $schema);
        if (is_wp_error($data)) {
            return $data;
        }

        if (!function_exists($data['method'])) {
            return new WP_Error(
                'method_is_not_function',
                __('Job method is not a function', 'forms-bridge'),
                $data
            );
        }

        return $data;
    }

    /**
     * Checks if payload compains with the required fields of the job.
     *
     * @param array $payload Input payload of the job.
     *
     * @return boolean
     */
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

    /**
     * Removes attributes from the payload that are not present on the job output config.
     *
     * @param array $payload Job result payload.
     *
     * @return array Filtered payload.
     */
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

// Autoload common workflow jobs
// $jobs_dir = dirname(__FILE__) . '/workflow-jobs';
// foreach (array_diff(scandir($jobs_dir), ['.', '..']) as $file) {
//     require_once $jobs_dir . '/' . $file;
// }
