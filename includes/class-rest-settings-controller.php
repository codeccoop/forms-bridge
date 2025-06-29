<?php

namespace FORMS_BRIDGE;

use Error;
use Exception;
use WP_Error;
use WP_REST_Server;
use WPCT_PLUGIN\REST_Settings_Controller as Base_Controller;
use FBAPI;

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
        self::register_backend_schema_route();
        self::register_backend_fetch_route();
        self::register_backend_ping_route();
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

        $registry = Addon::registry();

        foreach ($registry as $api => $enabled) {
            if (!$enabled) {
                continue;
            }

            $schema = Form_Bridge_Template::schema();
            $args = [];

            foreach ($schema['properties'] as $name => $prop_schema) {
                $args[$name] = $prop_schema;
                $args[$name]['required'] = in_array(
                    $name,
                    $schema['required'],
                    true
                );
            }

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/templates/(?P<name>[a-zA-Z0-9-]+)",
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => static function ($request) use ($api) {
                        $name = $request['name'];
                        return self::get_template($api, $name);
                    },
                    'permission_callback' => static function () {
                        return self::permission_callback();
                    },
                    'args' => [
                        'name' => $args['name'],
                    ],
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/templates/(?P<name>[a-zA-Z0-9-]+)",
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => static function ($request) use ($api) {
                        return self::save_template($api, $request);
                    },
                    'permission_callback' => static function () {
                        return self::permission_callback();
                    },
                    'args' => $args,
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/templates/(?P<name>[a-zA-Z0-9-]+)/use",
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) use ($api) {
                        return self::use_template($api, $request);
                    },
                    'permission_callback' => static function () {
                        return self::permission_callback();
                    },
                    'args' => [
                        'name' => $args['name'],
                        'integration' => [
                            'description' => __(
                                'Target integration',
                                'forms-bridge'
                            ),
                            'type' => 'string',
                            'required' => true,
                        ],
                        'fields' => $args['fields'],
                    ],
                ]
            );
        }
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

            $schema = Workflow_Job::schema();
            $args = [];

            foreach ($schema['properties'] as $name => $prop_schema) {
                $args[$name] = $prop_schema;
                $args[$name]['required'] = in_array(
                    $name,
                    $schema['required'],
                    true
                );
            }

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/jobs/(?P<name>[a-zA-Z0-9-]+)",
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
                            'name' => $args['name'],
                        ],
                    ],
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/jobs/(?P<name>[a-zA-Z0-9-]+)",
                [
                    [
                        'methods' => WP_REST_Server::EDITABLE,
                        'callback' => static function ($request) use ($api) {
                            return self::save_workflow_job($api, $request);
                        },
                        'permission_callback' => static function () {
                            return self::permission_callback();
                        },
                        'args' => [
                            'name' => $args['name'],
                            'title' => $args['title'],
                            'description' => $args['description'],
                            'input' => $args['input'],
                            'output' => $args['output'],
                            'snippet' => $args['snippet'],
                        ],
                    ],
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/workflow",
                [
                    [
                        'methods' => WP_REST_Server::CREATABLE,
                        'callback' => static function ($request) use ($api) {
                            $jobs = $request['jobs'];
                            return self::get_workflow_jobs($api, $jobs);
                        },
                        'permission_callback' => static function () {
                            return self::permission_callback();
                        },
                        'args' => [
                            'jobs' => [
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

    private static function register_backend_ping_route()
    {
        $namespace = self::namespace();
        $version = self::version();

        $registry = Addon::registry();

        foreach ($registry as $api => $enabled) {
            if (!$enabled) {
                continue;
            }

            $schema = Form_Bridge_Template::schema();
            $args = [];

            foreach ($schema['properties'] as $name => $prop_schema) {
                $args[$name] = $prop_schema;
                $args[$name]['required'] = in_array(
                    $name,
                    $schema['required'],
                    true
                );
            }

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/backend/ping",
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) use ($api) {
                        [
                            $backend,
                        ] = \HTTP_BRIDGE\Settings_Store::validate_backends([
                            $request['backend'],
                        ]);

                        if (empty($backend)) {
                            return new WP_Error(
                                'bad_request',
                                __('Backend data is invalid', 'forms-bridge'),
                                ['status' => 400]
                            );
                        }

                        return Addon::ping(
                            $api,
                            $backend,
                            $request['credential']
                        );
                    },
                    'permission_callback' => static function () {
                        return self::permission_callback();
                    },
                    'args' => [
                        'backend' => $args['backend'],
                        'credential' => $args['credential'],
                    ],
                ]
            );
        }
    }

    private static function register_backend_fetch_route()
    {
        $namespace = self::namespace();
        $version = self::version();

        $apis = Addon::registry();

        foreach ($apis as $api => $enabled) {
            if (!$enabled) {
                continue;
            }

            $schema = Form_Bridge_Template::schema();
            $args = [];

            foreach ($schema['properties'] as $name => $prop_schema) {
                $args[$name] = $prop_schema;
                $args[$name]['required'] = in_array(
                    $name,
                    $schema['required'],
                    true
                );
            }

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/backend/api/fetch",
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) use ($api) {
                        [
                            $backend,
                        ] = \HTTP_BRIDGE\Settings_Store::validate_backends([
                            $request['backend'],
                        ]);

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
                        'backend' => $args['backend'],
                        'endpoint' => [
                            'description' => __(
                                'Target endpoint name',
                                'forms-bridge'
                            ),
                            'type' => 'string',
                            'required' => true,
                        ],
                        'credential' => $args['credential'],
                    ],
                ]
            );
        }
    }

    private static function register_backend_schema_route()
    {
        $namespace = self::namespace();
        $version = self::version();

        $registry = Addon::registry();

        foreach ($registry as $api => $enabled) {
            if (!$enabled) {
                continue;
            }

            $schema = Form_Bridge_Template::schema();
            $args = [];

            foreach ($schema['properties'] as $name => $prop_schema) {
                $args[$name] = $prop_schema;
                $args[$name]['required'] = in_array(
                    $name,
                    $schema['required'],
                    true
                );
            }

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$api}/backend/api/schema",
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) use ($api) {
                        [
                            $backend,
                        ] = \HTTP_BRIDGE\Settings_Store::validate_backends([
                            $request['backend'],
                        ]);

                        if (empty($backend)) {
                            return new WP_Error(
                                'bad_request',
                                __('Backend data is invalid', 'forms-bridge'),
                                ['status' => 400]
                            );
                        }

                        return Addon::endpoint_schema(
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
                        'backend' => $args['backend'],
                        'endpoint' => [
                            'description' => __(
                                'Target endpoint name',
                                'forms-bridge'
                            ),
                            'type' => 'string',
                            'required' => true,
                        ],
                        'credential' => $args['credential'],
                    ],
                ]
            );
        }
    }

    /**
     * Callback for GET requests to the forms endpoint.
     *
     * @return array Collection of array forms data.
     */
    private static function forms()
    {
        $forms = FBAPI::get_forms();
        return array_map(static function ($form) {
            unset($form['bridges']);
            return $form;
        }, $forms);
    }

    /**
     * Callback for GET requests to the workflow job endpoint.
     *
     * @param string $api API name.
     * @param string $name Name of the workflow job.
     *
     * @return array|WP_Error Workflow job data.
     */
    private static function get_workflow_job($api, $name)
    {
        $job = FBAPI::get_job($name, $api);
        if (empty($job)) {
            return new WP_Error(
                'not_found',
                __('Workflow job not found', 'forms-bridge'),
                ['name' => $name]
            );
        }

        return $job->to_json();
    }

    private static function save_workflow_job($api, $request)
    {
        $config = [
            'name' => $request['name'],
            'title' => $request['title'],
            'description' => $request['description'],
            'input' => $request['input'],
            'output' => $request['output'],
            'snippet' => $request['snippet'],
        ];

        $job = new Workflow_Job($config, $api);
        $result = $job->save();

        if (is_wp_error($result)) {
            return $result;
        }

        return ['success' => true];
    }

    /**
     * Callback for POST requests to the workflow jobs endpoint.
     *
     * @param string $api API name.
     * @param string[] $job_names Array of job names.
     *
     * @return array|WP_Error Workflow jobs data.
     */
    private static function get_workflow_jobs($api, $job_names)
    {
        $api_jobs = FBAPI::get_api_jobs($api);

        $jobs = [];
        foreach ($api_jobs as $job) {
            if (in_array($job->name, $job_names, true)) {
                $jobs[] = $job->to_json();
            }
        }

        if (count($jobs) !== count($job_names)) {
            return new WP_Error(
                'not_found',
                __('Workflow jobs not found', 'forms-bridge'),
                ['jobs' => $job_names]
            );
        }

        return $jobs;
    }

    /**
     * Callback for GET requests to the templates endpoint.
     *
     * @param string $api Name of the owner addon of the template.
     * @param string $name Name of the template.
     *
     * @return array|WP_Error Template data.
     */
    private static function get_template($api, $name)
    {
        $template = FBAPI::get_template($name, $api);
        if (empty($template)) {
            return new WP_Error(
                'not_found',
                __('Template is unknown', 'forms-bridge'),
                ['name' => $name]
            );
        }

        return $template->to_json();
    }

    private static function save_template($api, $request)
    {
        $config = [
            'name' => $request['name'],
            'title' => $request['title'],
            'description' => $request['description'],
            'integrations' => $request['integrations'] ?? [],
            'fields' => $request['fields'],
            'form' => $request['form'],
            'bridge' => $request['bridge'],
        ];

        $template = new Form_Bridge_Template($config);
        $result = $template->save();

        if (is_wp_error($result)) {
            return $result;
        }

        return ['success' => true];
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
        $name = $request['name'];
        $fields = $request['fields'];
        $integration = $request['integration'];

        if (!in_array($integration, array_keys(Integration::integrations()))) {
            return new WP_Error(
                'bad_request',
                __('Invalid use template integration', 'forms-bridge'),
                ['integration' => $integration]
            );
        }

        $template = FBAPI::get_template($name, $api);
        if (empty($template)) {
            return new WP_Error(
                'not_found',
                __('Template is unknown', 'forms-bridge'),
                ['name' => $name]
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
