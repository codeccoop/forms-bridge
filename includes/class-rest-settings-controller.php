<?php

namespace FORMS_BRIDGE;

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
        self::register_schema_route();
        self::register_template_routes();
        self::register_job_routes();
        self::register_backend_routes();
        self::register_oauth_routes();
    }

    /**
     * Registers form REST API routes.
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
            'permission_callback' => [self::class, 'permission_callback'],
        ]);
    }

    private static function register_schema_route()
    {
        $namespace = self::namespace();
        $version = self::version();

        foreach (Addon::addons() as $addon) {
            if (!$addon->enabled) {
                continue;
            }

            $addon = $addon::name;
            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$addon}/schemas",
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => static function () use ($addon) {
                        return self::addon_schemas($addon);
                    },
                    'permission_callback' => [
                        self::class,
                        'permission_callback',
                    ],
                ]
            );
        }
    }

    /**
     * Registers templates REST API routes.
     */
    private static function register_template_routes()
    {
        $namespace = self::namespace();
        $version = self::version();

        foreach (Addon::addons() as $addon) {
            if (!$addon->enabled) {
                continue;
            }

            $addon = $addon::name;

            $schema = Form_Bridge_Template::schema($addon);
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
                "/{$addon}/templates/(?P<name>[a-zA-Z0-9-_]+)",
                [
                    [
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => static function ($request) use ($addon) {
                            return self::get_template($addon, $request);
                        },
                        'permission_callback' => [
                            self::class,
                            'permission_callback',
                        ],
                        'args' => [
                            'name' => $args['name'],
                        ],
                    ],
                    [
                        'methods' => WP_REST_Server::EDITABLE,
                        'callback' => static function ($request) use ($addon) {
                            return self::save_template($addon, $request);
                        },
                        'permission_callback' => [
                            self::class,
                            'permission_callback',
                        ],
                        'args' => $args,
                    ],
                    [
                        'methods' => WP_REST_Server::DELETABLE,
                        'callback' => static function ($request) use ($addon) {
                            return self::reset_template($addon, $request);
                        },
                        'permission_callback' => [
                            self::class,
                            'permission_callback',
                        ],
                        'args' => [
                            'name' => $args['name'],
                        ],
                    ],
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$addon}/templates/(?P<name>[a-zA-Z0-9-_]+)/backend",
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) use ($addon) {
                        return self::transient_backend($addon, $request);
                    },
                    'permission_callback' => [
                        self::class,
                        'permission_callback',
                    ],
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

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$addon}/templates/(?P<name>[a-zA-Z0-9-_]+)/credential",
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) use ($addon) {
                        return self::transient_credential($addon, $request);
                    },
                    'permission_callback' => [
                        self::class,
                        'permission_callback',
                    ],
                    'args' => [],
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$addon}/templates/(?P<name>[a-zA-Z0-9-_]+)/use",
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) use ($addon) {
                        return self::use_template($addon, $request);
                    },
                    'permission_callback' => [
                        self::class,
                        'permission_callback',
                    ],
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

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$addon}/templates/(?P<name>[a-zA-Z0-9-_]+)/options",
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) use ($addon) {
                        return self::get_template_options($addon, $request);
                    },
                    'permission_callback' => [
                        self::class,
                        'permission_callback',
                    ],
                    'args' => [
                        'name' => $args['name'],
                        'backend' => $args['backend'],
                        'credential' => $args['credential'],
                    ],
                ]
            );
        }
    }

    /**
     * Registers jobs REST API routes.
     */
    private static function register_job_routes()
    {
        $namespace = self::namespace();
        $version = self::version();

        foreach (Addon::addons() as $addon) {
            if (!$addon->enabled) {
                continue;
            }

            $addon = $addon::name;

            $schema = Job::schema();
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
                "/{$addon}/jobs/workflow",
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => static function ($request) use ($addon) {
                        return self::get_jobs($addon, $request);
                    },
                    'permission_callback' => [
                        self::class,
                        'permission_callback',
                    ],
                    'args' => [
                        'jobs' => [
                            'description' => __(
                                'Array of job names',
                                'forms-bridge'
                            ),
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'uniqueItems' => true,
                            'minItems' => 1,
                            'required' => true,
                        ],
                    ],
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$addon}/jobs/(?P<name>[a-zA-Z0-9-_]+)",
                [
                    [
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => static function ($request) use ($addon) {
                            return self::get_job($addon, $request);
                        },
                        'permission_callback' => [
                            self::class,
                            'permission_callback',
                        ],
                        'args' => [
                            'name' => $args['name'],
                        ],
                    ],
                    [
                        'methods' => WP_REST_Server::EDITABLE,
                        'callback' => static function ($request) use ($addon) {
                            return self::save_job($addon, $request);
                        },
                        'permission_callback' => [
                            self::class,
                            'permission_callback',
                        ],
                        'args' => [
                            'name' => $args['name'],
                            'title' => $args['title'],
                            'description' => $args['description'],
                            'input' => $args['input'],
                            'output' => $args['output'],
                            'snippet' => $args['snippet'],
                        ],
                    ],
                    [
                        'methods' => WP_REST_Server::DELETABLE,
                        'callback' => static function ($request) use ($addon) {
                            return self::reset_job($addon, $request);
                        },
                        'permission_callback' => [
                            self::class,
                            'permission_callback',
                        ],
                        'args' => [
                            'name' => $args['name'],
                        ],
                    ],
                ]
            );
        }
    }

    private static function register_backend_routes()
    {
        $namespace = self::namespace();
        $version = self::version();

        foreach (Addon::addons() as $addon) {
            if (!$addon->enabled) {
                continue;
            }

            $addon = $addon::name;

            $schema = Form_Bridge_Template::schema($addon);
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
                "/{$addon}/backend/ping",
                [
                    [
                        'methods' => WP_REST_Server::CREATABLE,
                        'callback' => static function ($request) use ($addon) {
                            return self::ping_backend($addon, $request);
                        },
                        'permission_callback' => [
                            self::class,
                            'permission_callback',
                        ],
                        'args' => [
                            'backend' => $args['backend'],
                            'credential' => $args['credential'],
                        ],
                    ],
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$addon}/backend/endpoint/schema",
                [
                    [
                        'methods' => WP_REST_Server::CREATABLE,
                        'callback' => static function ($request) use ($addon) {
                            return self::get_endpoint_schema($addon, $request);
                        },
                        'permission_callback' => [
                            self::class,
                            'permission_callback',
                        ],
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
                    ],
                ]
            );
        }
    }

    private static function register_oauth_routes()
    {
        $namespace = self::namespace();
        $version = self::version();

        foreach (Addon::addons() as $addon) {
            if (!$addon->enabled) {
                continue;
            }

            $addon = $addon::name;

            $schema = Form_Bridge_Template::schema($addon);
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
                "{$addon}/oauth/grant",
                [
                    [
                        'methods' => WP_REST_Server::CREATABLE,
                        'callback' => static function ($request) use ($addon) {
                            return self::oauth_grant($addon, $request);
                        },
                        'permission_callback' => [
                            self::class,
                            'permission_callback',
                        ],
                        'args' => $args['credential'],
                    ],
                ]
            );

            register_rest_route(
                "{$namespace}/v{$version}",
                "/{$addon}/oauth/redirect",
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => static function ($request) use ($addon) {
                        return self::oauth_redirect($addon, $request);
                    },
                    'permission_callback' => [
                        self::class,
                        'permission_callback',
                    ],
                    'args' => [
                        'name' => [
                            'type' => 'string',
                            'description' => __(
                                'Credential name',
                                'forms-bridge'
                            ),
                            'required' => true,
                            'minLength' => 1,
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * Callback for GET requests to the forms endpoint.
     *
     * @return array
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
     * Callback for GET requests to the job endpoint.
     *
     * @param string $addon Addon name.
     * @param WP_REST_Request $request.
     *
     * @return array|WP_Error
     */
    private static function get_job($addon, $request)
    {
        $job = FBAPI::get_job($request['name'], $addon);
        if (empty($job)) {
            return self::not_found();
        }

        return $job->data();
    }

    private static function save_job($addon, $request)
    {
        $data = $request->get_json_params();
        $data['name'] = $request['name'];

        $post_id = FBAPI::save_job($data, $addon);
        if (!$post_id) {
            return self::bad_request();
        }

        $post = get_post($post_id);
        return (new Job($post, $addon))->data();
    }

    private static function reset_job($addon, $request)
    {
        $job = FBAPI::get_job($request['name'], $addon);

        if (!$job) {
            return self::not_found();
        }

        $job = $job->reset();
        if (!$job) {
            return ['success' => false];
        }

        $job = FBAPI::get_job($request['name'], $addon);
        if ($job) {
            return $job->data();
        }
    }

    /**
     * Callback for POST requests to the jobs endpoint.
     *
     * @param string $addon Addon name.
     * @param WP_REST_Request $request.
     *
     * @return array|WP_Error Jobs data.
     */
    private static function get_jobs($addon, $request)
    {
        $jobs = [];
        foreach (FBAPI::get_addon_jobs($addon) as $job) {
            if (in_array($job->name, $request['jobs'], true)) {
                $jobs[] = $job->data();
            }
        }

        if (count($jobs) !== count($request['jobs'])) {
            return self::not_found();
        }

        return $jobs;
    }

    /**
     * Callback for GET requests to the templates endpoint.
     *
     * @param string $addon Addon name.
     * @param WP_REST_Request $request.
     *
     * @return array|WP_Error Template data.
     */
    private static function get_template($addon, $request)
    {
        $template = FBAPI::get_template($request['name'], $addon);
        if (empty($template)) {
            return self::not_found(__('Template is unknown', 'forms-bridge'), [
                'name' => $request['name'],
                'addon' => $addon,
            ]);
        }

        return $template->data();
    }

    private static function save_template($addon, $request)
    {
        $data = $request->get_json_params();
        $data['name'] = $request['name'];

        $result = FBAPI::save_template($data, $addon);
        if (is_wp_error($result)) {
            return $result;
        }

        return ['success' => true];
    }

    private static function reset_template($addon, $name)
    {
        $template = FBAPI::get_template($name, $addon);

        if (!$template) {
            return self::not_found();
        }

        $result = $template->reset();

        if (!$result) {
            return self::internal_server_error();
        }

        $template = FBAPI::get_template($name, $addon);
        if ($template) {
            return $template->data();
        }
    }

    /**
     * Callback for POST requests to the templates endpoint.
     *
     * @param string $addon Name of the owner addon of the template.
     * @param REST_Request Request instance.
     *
     * @return array|WP_Error Template use result.
     */
    private static function use_template($addon, $request)
    {
        $name = $request['name'];
        $fields = $request['fields'];
        $integration = $request['integration'];

        $template = FBAPI::get_template($name, $addon);
        if (empty($template)) {
            return self::not_found();
        }

        if (!in_array($integration, $template->integrations)) {
            return self::bad_request();
        }

        $result = $template->use($fields, $integration);

        if (is_wp_error($result)) {
            return $result;
        }

        return ['success' => $result === true];
    }

    private static function get_template_options($addon, $request)
    {
        [$backend] = \HTTP_BRIDGE\Settings_Store::sanitize_backends([
            $request['backend'],
        ]);

        if (empty($backend)) {
            return self::bad_request();
        }

        $template = FBAPI::get_template($request['name'], $addon);
        if (!$template) {
            return self::not_found();
        }

        if (!$template->is_valid) {
            return self::bad_request();
        }

        $field_options = [];
        $fields = $template->fields;
        foreach ($fields as $field) {
            if (isset($field['options']['endpoint'])) {
                if (is_string($field['options']['finger'])) {
                    $field['options']['finger'] = [
                        'value' => $field['options']['finger'],
                    ];
                }

                $value_pointer = $field['options']['finger']['value'];

                if (!JSON_Finger::validate($value_pointer)) {
                    return self::internal_server_error();
                }

                if (
                    $label_pointer =
                        $field['options']['finger']['label'] ??
                        $field['options']['finger']['value']
                ) {
                    if (!JSON_Finger::validate($label_pointer)) {
                        return self::internal_server_error();
                    }
                }

                $response = Addon::fetch(
                    $addon,
                    $backend,
                    $field['options']['endpoint'],
                    $request['credential']
                );

                if (is_wp_error($response)) {
                    $error = self::internal_server_error();
                    $error->add($response);
                    return $error;
                }

                $options = [];
                $data = $response['data'];

                $finger = new JSON_Finger($data);

                $values = $finger->get($value_pointer);

                if (!wp_is_numeric_array($values)) {
                    return self::internal_server_error();
                }

                foreach ($values as $value) {
                    $options[] = ['value' => $value, 'label' => $value];
                }

                $labels = $finger->get($label_pointer);
                if (
                    wp_is_numeric_array($labels) &&
                    count($labels) === count($values)
                ) {
                    for ($i = 0; $i < count($labels); $i++) {
                        $options[$i]['label'] = $labels[$i];
                    }
                }

                $field_options[] = [
                    'ref' => $field['ref'],
                    'name' => $field['name'],
                    'options' => $options,
                ];
            }
        }

        return $field_options;
    }

    private static function ping_backend($addon, $request)
    {
        [$backend] = \HTTP_BRIDGE\Settings_Store::sanitize_backends([
            $request['backend'],
        ]);

        if (empty($backend)) {
            return self::bad_request();
        }

        $result = Addon::ping($addon, $backend, $request['credential']);

        if (is_wp_error($result)) {
            $error = self::bad_request();
            $error->add($result);
            return $error;
        }

        return $result;
    }

    private static function get_endpoint_schema($addon, $request)
    {
        [$backend] = \HTTP_BRIDGE\Settings_Store::sanitize_backends([
            $request['backend'],
        ]);

        if (empty($backend)) {
            return self::bad_request();
        }

        $schema = Addon::endpoint_schema(
            $addon,
            $backend,
            $request['endpoint'],
            $request['credential']
        );

        if (is_wp_error($schema)) {
            $error = self::internal_server_error();
            $error->add($schema);
            return $error;
        }

        return $schema;
    }

    private static function addon_schemas($name)
    {
        $addon = Addon::addon($name);

        $bridge_class = $addon::bridge_class;
        $bridge = $bridge_class::schema();
        $template = Form_Bridge_Template::schema($addon::name);

        return [
            'bridge' => $bridge,
            'template' => $template,
        ];
    }

    private static function oauth_grant($addon, $request)
    {
        $addon = Addon::addon($addon);

        $redirect = $addon->oauth_grant($request['name']);
        if (is_wp_error($redirect)) {
            $error = self::internal_server_error();
            $error->add($redirect);
        }

        if (!$redirect) {
            return self::bad_request();
        }

        return ['redirect' => $redirect];
    }

    private static function oauth_redirect($addon, $request)
    {
        $addon = Addon::addon($addon);
        $result = $addon->oauth_redirect_callback($request);

        if (!$result) {
            return self::bad_request('bad_request');
        }

        return ['success' => true];
    }

    private static function transient_backend($addon, $request) {}

    private static function transient_credential($adodn, $request) {}
}
