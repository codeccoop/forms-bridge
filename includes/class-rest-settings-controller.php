<?php

namespace FORMS_BRIDGE;

use Error;
use Exception;
use WP_Error;
use WP_REST_Server;
use WPCT_PLUGIN\REST_Settings_Controller as Base_Controller;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Plugin REST API controller. Handles routes registration, permissions
 * and request callbacks.
 */
class REST_Settings_Controller extends Base_Controller
{
    /**
     * Inherits the parent initialized and register the post types route
     *
     * @param string $group Plugin settings group name.
     */
    protected static function init()
    {
        parent::init();
        self::register_forms_route();
        self::register_templates_route();
        self::register_workflow_jobs_route();
        self::register_api_schema_route();
        self::register_api_fetch_route();
        self::register_api_ping_route();
    }

    /**
     * Registers form API routes.
     */
    private static function register_forms_route()
    {
        // forms endpoint registration
        $namespace = self::namespace();
        $version = self::version();

        register_rest_route("{$namespace}/v{$version}", '/forms', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => static function () {
                return self::forms();
            },
            'permission_callback' => static function () {
                return self::permission_callback();
            },
        ]);
    }

    /**
     * Registers templates API routes.
     */
    private static function register_templates_route()
    {
        $namespace = self::namespace();
        $version = self::version();

        register_rest_route(
            "{$namespace}/v{$version}",
            '/templates/(?P<name>[a-zA-Z0-9-]+)',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => static function ($request) {
                    $template_name = $request['name'];
                    $api = explode('-', $template_name)[0];
                    return self::get_template($api, $template_name);
                },
                'permission_callback' => static function () {
                    return self::permission_callback();
                },
                'args' => [
                    'name' => [
                        'description' => __(
                            'Name of the template',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
            ]
        );

        register_rest_route("{$namespace}/v{$version}", '/templates', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => static function ($request) {
                $api = explode('-', $request['name'])[0];
                return self::use_template($api, $request);
            },
            'permission_callback' => static function () {
                return self::permission_callback();
            },
            'args' => [
                'name' => [
                    'description' => __('Name of the template', 'forms-bridge'),
                    'type' => 'string',
                    'required' => true,
                ],
                'integration' => [
                    'description' => __('Target integration', 'forms-bridge'),
                    'type' => 'string',
                    'required' => true,
                ],
                'fields' => [
                    'description' => __(
                        'Template fields with user inputs as values',
                        'forms-bridge'
                    ),
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'ref' => [
                                'description' => __(
                                    'Field ref that points to some template param',
                                    'forms-bridge'
                                ),
                                'type' => 'string',
                                'required' => true,
                            ],
                            'name' => [
                                'description' => __(
                                    'Name of the field',
                                    'forms-bridge'
                                ),
                                'type' => 'string',
                                'required' => true,
                            ],
                            'value' => [
                                'description' => __(
                                    'Field value',
                                    'forms-bridge'
                                ),
                                'type' => 'mixed',
                            ],
                        ],
                    ],
                    'required' => true,
                ],
            ],
        ]);
    }

    /**
     * Registers workflow jobs API routes.
     */
    private static function register_workflow_jobs_route()
    {
        $namespace = self::namespace();
        $version = self::version();

        $registry = Addon::registry();

        foreach ($registry as $api => $enabled) {
            if (!$enabled) {
                continue;
            }

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/workflow_jobs/(?P<name>[a-zA-Z0-9-]+)",
                [
                    [
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => static function ($request) use ($api) {
                            $name = $request['name'];
                            return self::get_workflow_job($api, $name);
                        },
                        'permission_callback' => static function () {
                            return self::permission_callback();
                        },
                        'args' => [
                            'name' => [
                                'description' => __(
                                    'Name of the workflow job',
                                    'forms-bridge'
                                ),
                                'type' => 'string',
                                'required' => true,
                            ],
                        ],
                    ],
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/workflow_jobs",
                [
                    [
                        'methods' => WP_REST_Server::CREATABLE,
                        'callback' => static function ($request) use ($api) {
                            $workflow = $request['workflow'];
                            return self::get_workflow_jobs($api, $workflow);
                        },
                        'permission_callback' => static function () {
                            return self::permission_callback();
                        },
                        'args' => [
                            'workflow' => [
                                'description' => __(
                                    'Array of workflow job names',
                                    'forms-bridge'
                                ),
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'uniqueItems' => true,
                                'minItems' => 1,
                                'required' => true,
                            ],
                        ],
                    ],
                ]
            );
        }
    }

    private static function register_api_ping_route()
    {
        $namespace = self::namespace();
        $version = self::version();

        $registry = Addon::registry();

        foreach ($registry as $api => $enabled) {
            if (!$enabled) {
                continue;
            }

            register_rest_route("{$namespace}/v{$version}", "/{$api}/ping", [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => static function ($request) use ($api) {
                    [$backend] = \HTTP_BRIDGE\Settings_Store::validate_backends(
                        [$request['backend']]
                    );

                    if (empty($backend)) {
                        return new WP_Error(
                            'bad_request',
                            __('Backend data is invalid', 'forms-bridge'),
                            ['status' => 400]
                        );
                    }

                    return Addon::ping($api, $backend, $request['credential']);
                },
                'permission_callback' => static function () {
                    return self::permission_callback();
                },
                'args' => [
                    'backend' => [
                        'description' => __(
                            'Backend data to be used on the request',
                            'forms-bridge'
                        ),
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'base_url' => ['type' => 'string'],
                            'headers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'value' => ['type' => 'string'],
                                    ],
                                    'required' => ['name', 'value'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'additionalProperties' => false,
                        'required' => true,
                    ],
                    'credential' => [
                        'description' => __('API credentials', 'forms-bridge'),
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'minLength' => 1,
                            ],
                        ],
                        'additionalProperties' => true,
                        'required' => false,
                    ],
                ],
            ]);
        }
    }

    private static function register_api_fetch_route()
    {
        $namespace = self::namespace();
        $version = self::version();

        $apis = Addon::registry();

        foreach ($apis as $api => $enabled) {
            if (!$enabled) {
                continue;
            }

            register_rest_route("{$namespace}/v{$version}", "/{$api}/fetch", [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => static function ($request) use ($api) {
                    [$backend] = \HTTP_BRIDGE\Settings_Store::validate_backends(
                        [$request['backend']]
                    );

                    if (empty($backend)) {
                        return new WP_Error(
                            'bad_request',
                            __('Backend data is invalid', 'forms-bridge'),
                            ['status' => 400]
                        );
                    }

                    return Addon::fetch(
                        $api,
                        $backend,
                        $request['endpoint'],
                        $request['credential']
                    );
                },
                'permission_callback' => static function () {
                    return self::permission_callback();
                },
                'args' => [
                    'backend' => [
                        'description' => __(
                            'Backend data to be used on the request',
                            'forms-bridge'
                        ),
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'base_url' => ['type' => 'string'],
                            'headers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'value' => ['type' => 'string'],
                                    ],
                                    'required' => ['name', 'value'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['name', 'base_url', 'headers'],
                        'additionalProperties' => false,
                    ],
                    'endpoint' => [
                        'description' => __(
                            'Target endpoint name',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                    'credential' => [
                        'description' => __('API credentials', 'forms-bridge'),
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'minLength' => 1,
                            ],
                        ],
                        'additionalProperties' => true,
                        'required' => false,
                    ],
                ],
            ]);
        }
    }

    private static function register_api_schema_route()
    {
        $namespace = self::namespace();
        $version = self::version();

        $registry = Addon::registry();

        foreach ($registry as $api => $enabled) {
            if (!$enabled) {
                continue;
            }

            register_rest_route("{$namespace}/v{$version}", "/{$api}/schema", [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => static function ($request) use ($api) {
                    [$backend] = \HTTP_BRIDGE\Settings_Store::validate_backends(
                        [$request['backend']]
                    );
                    if (empty($backend)) {
                        return new WP_Error(
                            'bad_request',
                            __('Backend data is invalid', 'forms-bridge'),
                            ['status' => 400]
                        );
                    }

                    return Addon::schema(
                        $api,
                        $backend,
                        $request['endpoint'],
                        $request['credential']
                    );
                },
                'permission_callback' => static function () {
                    return self::permission_callback();
                },
                'args' => [
                    'backend' => [
                        'description' => __(
                            'Backend data to be used on the request',
                            'forms-bridge'
                        ),
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'base_url' => ['type' => 'string'],
                            'headers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'value' => ['type' => 'string'],
                                    ],
                                    'required' => ['name', 'value'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['name', 'base_url', 'headers'],
                        'additionalProperties' => false,
                    ],
                    'endpoint' => [
                        'description' => __(
                            'Target endpoint name',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                    'credential' => [
                        'description' => __('API credentials', 'forms-bridge'),
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'minLength' => 1,
                            ],
                        ],
                        'additionalProperties' => true,
                        'required' => false,
                    ],
                ],
            ]);
        }
    }

    /**
     * Callback for GET requests to the forms endpoint.
     *
     * @return array Collection of array forms data.
     */
    private static function forms()
    {
        $forms = apply_filters('forms_bridge_forms', []);
        return array_map(static function ($form) {
            unset($form['bridges']);
            return $form;
        }, $forms);
    }

    /**
     * Callback for GET requests to the workflow job endpoint.
     *
     * @param $job_name Name of the workflow job.
     *
     * @return array|WP_Error Workflow job data.
     */
    private static function get_workflow_job($api, $job_name)
    {
        $jobs = apply_filters('forms_bridge_workflow_jobs', [], $api);
        foreach ($jobs as $candidate) {
            if ($candidate->name === $job_name) {
                $job = $candidate;
            }
        }

        if (!isset($job)) {
            return new WP_Error(
                'not_found',
                __('Workflow job not found', 'forms-bridge'),
                ['name' => $job_name]
            );
        }

        return $job->to_json();
    }

    /**
     * Callback for POST requests to the workflow jobs endpoint.
     *
     * @param REST_Request Request instance.
     *
     * @return array|WP_Error Workflow jobs data.
     */
    private static function get_workflow_jobs($api, $workflow)
    {
        $api_jobs = apply_filters('forms_bridge_workflow_jobs', [], $api);

        $jobs = array_filter($api_jobs, function ($job) use ($workflow) {
            return in_array($job->name, $workflow, true);
        });

        if (count($jobs) !== count($workflow)) {
            return new WP_Error(
                'not_found',
                __('Workflow jobs not found', 'forms-bridge'),
                ['workflow' => $workflow]
            );
        }

        return array_values(
            array_map(function ($job) {
                return $job->to_json();
            }, $jobs)
        );
    }

    /**
     * Callback for GET requests to the templates endpoint.
     *
     * @param string $api Name of the owner addon of the template.
     * @param string $template_name Name of the template.
     *
     * @return array|WP_Error Template data.
     */
    private static function get_template($api, $template_name)
    {
        $templates = apply_filters('forms_bridge_templates', [], $api);

        foreach ($templates as $candidate) {
            if ($candidate->name === $template_name) {
                $template = $candidate;
                break;
            }
        }

        if (!isset($template)) {
            return new WP_Error(
                'not_found',
                __('Template is unknown', 'forms-bridge'),
                ['name' => $template_name]
            );
        }

        return $template->to_json();
    }

    /**
     * Callback for POST requests to the templates endpoint.
     *
     * @param string $api Name of the owner addon of the template.
     * @param REST_Request Request instance.
     *
     * @return array|WP_Error Template use result.
     */
    private static function use_template($api, $request)
    {
        $template_name = $request['name'];
        $fields = $request['fields'];
        $integration = $request['integration'];

        if (!in_array($integration, array_keys(Integration::integrations()))) {
            return new WP_Error(
                'bad_request',
                __('Invalid use template integration', 'forms-bridge'),
                ['integration' => $integration]
            );
        }

        $templates = apply_filters('forms_bridge_templates', [], $api);
        foreach ($templates as $candidate) {
            if ($candidate->name === $template_name) {
                $template = $candidate;
                break;
            }
        }

        if (!isset($template)) {
            return new WP_Error(
                'not_found',
                __('Template is unknown', 'forms-bridge'),
                ['name' => $template_name, 'api' => $api]
            );
        }

        try {
            $template->use($fields, $integration);
            return ['success' => true];
        } catch (Form_Bridge_Template_Exception $e) {
            // Use custom exception to catch custom error status
            return new WP_Error($e->getStringCode(), $e->getMessage());
        } catch (Error | Exception $e) {
            return new WP_Error('internal_server_error', $e->getMessage());
        }
    }
}
