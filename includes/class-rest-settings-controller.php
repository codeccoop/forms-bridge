<?php

namespace FORMS_BRIDGE;

use WP_Error;
use WP_REST_Server;
use WPCT_PLUGIN\REST_Settings_Controller as Base_Controller;
use FBAPI;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

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
    }

    /**
     * Registers form REST API routes.
     */
    private static function register_forms_route()
    {
        register_rest_route('forms-bridge/v1', '/forms', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => static function () {
                return self::forms();
            },
            'permission_callback' => [self::class, 'permission_callback'],
        ]);
    }

    private static function register_schema_route()
    {
        foreach (Addon::addons() as $addon) {
            if (!$addon->enabled) {
                continue;
            }

            $addon = $addon::name;
            register_rest_route('forms-bridge/v1', "/{$addon}/schemas", [
                'methods' => WP_REST_Server::READABLE,
                'callback' => static function () use ($addon) {
                    return self::addon_schemas($addon);
                },
                'permission_callback' => [self::class, 'permission_callback'],
            ]);
        }

        register_rest_route('forms-bridge/v1', '/http/schemas', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => static function () {
                return self::http_schemas();
            },
            'permission_callback' => [self::class, 'permission_callback'],
        ]);
    }

    /**
     * Registers templates REST API routes.
     */
    private static function register_template_routes()
    {
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
                'forms-bridge/v1',
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
                'forms-bridge/v1',
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
                'forms-bridge/v1',
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
                        'backend' => FBAPI::get_backend_schema(),
                        'credential' => FBAPI::get_credential_schema(),
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

            register_rest_route('forms-bridge/v1', "/{$addon}/jobs/workflow", [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => static function ($request) use ($addon) {
                    return self::get_jobs($addon, $request);
                },
                'permission_callback' => [self::class, 'permission_callback'],
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
            ]);

            register_rest_route(
                'forms-bridge/v1',
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
                        'args' => $args,
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
        foreach (Addon::addons() as $addon) {
            if (!$addon->enabled) {
                continue;
            }

            $addon = $addon::name;

            // $schema = Form_Bridge_Template::schema($addon);
            // $args = [];

            // foreach ($schema['properties'] as $name => $prop_schema) {
            //     $args[$name] = $prop_schema;
            //     $args[$name]['required'] = in_array(
            //         $name,
            //         $schema['required'],
            //         true
            //     );
            // }

            register_rest_route('forms-bridge/v1', "/{$addon}/backend/ping", [
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
                        'backend' => FBAPI::get_backend_schema(),
                        'credential' => FBAPI::get_credential_schema(),
                    ],
                ],
            ]);

            register_rest_route(
                'forms-bridge/v1',
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
                            'backend' => FBAPI::get_backend_schema(),
                            'endpoint' => [
                                'description' => __(
                                    'Target endpoint name',
                                    'forms-bridge'
                                ),
                                'type' => 'string',
                                'required' => true,
                            ],
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

        $reset = $job->reset();
        if (!$reset) {
            return $job->data();
        }

        $job = FBAPI::get_job($request['name'], $addon);
        if ($job) {
            return $job->data();
        }

        return [];
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
        $handler = self::prepare_addon_backend_request_handler(
            $addon,
            $request
        );
        if (is_wp_error($handler)) {
            return $handler;
        }

        [$addon, $backend] = $handler;

        $template = FBAPI::get_template($request['name'], $addon::name);
        if (!$template) {
            return self::not_found();
        }

        if (!$template->is_valid) {
            return self::bad_request();
        }

        $field_options = [];
        $fields = $template->fields;
        foreach ($fields as $field) {
            if ($endpoint = $field['options']['endpoint'] ?? null) {
                if (is_string($field['options']['finger'])) {
                    $finger = [
                        'value' => $field['options']['finger'],
                    ];
                } else {
                    $finger = $field['options']['finger'];
                }

                $value_pointer = $finger['value'];

                if (!JSON_Finger::validate($value_pointer)) {
                    return self::internal_server_error();
                }

                $label_pointer = $finger['label'] ?? $finger['value'];

                if (!JSON_Finger::validate($label_pointer)) {
                    return self::internal_server_error();
                }

                $response = $addon->fetch($endpoint, $backend);

                if (is_wp_error($response)) {
                    $error = self::internal_server_error();
                    $error->add(
                        $response->get_error_code(),
                        $response->get_error_message(),
                        $response->get_error_data()
                    );

                    return $error;
                }

                $options = [];
                $data = $response['data'];

                $json_finger = new JSON_Finger($data);

                $values = $json_finger->get($value_pointer);

                if (!wp_is_numeric_array($values)) {
                    return self::internal_server_error();
                }

                foreach ($values as $value) {
                    $options[] = ['value' => $value, 'label' => $value];
                }

                $labels = $json_finger->get($label_pointer);
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

    /**
     * Performs a request validation and sanitization
     *
     * @param string $addon Target addon name.
     * @param WP_REST_Request $request Request instance.
     *
     * @return [Addon, string, string|null]|WP_Error
     */
    private static function prepare_addon_backend_request_handler(
        $addon,
        $request
    ) {
        $backend = wpct_plugin_sanitize_with_schema(
            $request['backend'],
            FBAPI::get_backend_schema()
        );

        if (is_wp_error($backend)) {
            return self::bad_request();
        }

        $credential = $request['credential'];
        if (!empty($credential)) {
            $credential = wpct_plugin_sanitize_with_schema(
                $credential,
                FBAPI::get_credential_schema($addon)
            );

            if (is_wp_error($credential)) {
                return self::bad_request();
            }

            $backend['credential'] = $credential['name'];
        }

        $addon = FBAPI::get_addon($addon);
        if (!$addon) {
            return self::bad_request();
        }

        Backend::temp_registration($backend);
        Credential::temp_registration($credential);

        return [$addon, $backend['name'], $credential['name'] ?? null];
    }

    private static function ping_backend($addon, $request)
    {
        $handler = self::prepare_addon_backend_request_handler(
            $addon,
            $request
        );

        if (is_wp_error($handler)) {
            return $handler;
        }

        [$addon, $backend] = $handler;

        $result = $addon->ping($backend);

        if (is_wp_error($result)) {
            $error = self::bad_request();
            $error->add(
                $result->get_error_code(),
                $result->get_error_message(),
                $result->get_error_data()
            );

            return $error;
        }

        return ['success' => $result];
    }

    private static function get_endpoint_schema($addon, $request)
    {
        $handler = self::prepare_addon_backend_request_handler(
            $addon,
            $request
        );
        if (is_wp_error($handler)) {
            return $handler;
        }

        [$addon, $backend] = $handler;

        $schema = $addon->get_endpoint_schema($request['endpoint'], $backend);

        if (is_wp_error($schema)) {
            $error = self::internal_server_error();
            $error->add(
                $schema->get_error_code(),
                $schema->get_error_message(),
                $schema->get_error_data()
            );

            return $error;
        }

        return $schema;
    }

    private static function addon_schemas($name)
    {
        $bridge = FBAPI::get_bridge_schema($name);
        return ['bridge' => $bridge];
    }

    private static function http_schemas()
    {
        $backend = FBAPI::get_backend_schema();
        $credential = FBAPI::get_credential_schema();
        return ['backend' => $backend, 'credential' => $credential];
    }
}
