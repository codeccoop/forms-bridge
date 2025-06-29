<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge object.
 */
abstract class Form_Bridge
{
    use Form_Bridge_Custom_Fields;
    use Form_Bridge_Mutations;

    /**
     * Bridge data common schema.
     *
     * @var array
     */
    public static function schema()
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'name' => [
                    'description' => __(
                        'Unique name of the bridge',
                        'forms-bridge'
                    ),
                    'type' => 'string',
                    'minLength' => 1,
                ],
                'form_id' => [
                    'description' => __(
                        'Internal form id with api prefix',
                        'forms-bridge'
                    ),
                    'type' => 'string',
                    'default' => '',
                ],
                'backend' => [
                    'description' => __('Backend name', 'forms-bridge'),
                    'type' => 'string',
                    'default' => '',
                ],
                'credential' => [
                    'description' => __('Credential name', 'forms-bridge'),
                    'type' => 'string',
                    'default' => '',
                ],
                'custom_fields' => [
                    'description' => __(
                        'Array of bridge\'s custom fields',
                        'forms-bridge'
                    ),
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'minLength' => 1,
                            ],
                            'value' => [
                                'type' => 'string',
                                'minLength' => 1,
                            ],
                        ],
                        'additionalProperties' => false,
                        'required' => ['name', 'value'],
                    ],
                    'default' => [],
                ],
                'mutations' => [
                    'description' => __(
                        'Stack of bridge mutations',
                        'forms-bridge'
                    ),
                    'type' => 'array',
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'properties' => [
                                'from' => [
                                    'type' => 'string',
                                    'minLength' => 1,
                                ],
                                'to' => [
                                    'type' => 'string',
                                    'minLength' => 1,
                                ],
                                'cast' => [
                                    'type' => 'string',
                                    'enum' => [
                                        'boolean',
                                        'string',
                                        'integer',
                                        'number',
                                        'json',
                                        'csv',
                                        'concat',
                                        'join',
                                        'sum',
                                        'count',
                                        'inherit',
                                        'copy',
                                        'null',
                                    ],
                                ],
                            ],
                            'additionalProperties' => false,
                            'required' => ['from', 'to', 'cast'],
                        ],
                    ],
                    'default' => [],
                ],
                'workflow' => [
                    'description' => __(
                        'Chain of workflow job names',
                        'forms-bridge'
                    ),
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'default' => [],
                ],
                'is_valid' => [
                    'description' => __(
                        'Validation result of the bridge setting',
                        'forms-bridge'
                    ),
                    'type' => 'boolean',
                    'default' => true,
                ],
                'enabled' => [
                    'description' => __(
                        'Boolean flag to enable/disable a bridge',
                        'forms-bridge'
                    ),
                    'type' => 'boolean',
                    'default' => true,
                ],
            ],
            'required' => [
                'name',
                'form_id',
                'backend',
                'custom_fields',
                'mutations',
                'workflow',
                'is_valid',
                'enabled',
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * Handles the form bridge's settings data.
     *
     * @var array
     */
    protected $data;

    protected $id;

    /**
     * Handles form bridge's api slug.
     *
     * @var string
     */
    protected $api;

    /**
     * Handles the array of accepted HTTP header names of the bridge API.
     *
     * @var array<string>
     */
    protected static $api_headers = [];

    /**
     * Stores the form bridge's data as a private attribute.
     */
    public function __construct($data)
    {
        $this->data = wpct_plugin_validate_with_schema($data, static::schema());

        if (!is_wp_error($this->data)) {
            $this->id = $this->api . '-' . $data['name'];

            add_filter(
                'forms_bridge_bridges',
                function ($bridges, $form_id = null, $api = null) {
                    if (!wp_is_numeric_array($bridges)) {
                        $bridges = [];
                    }

                    if ($form_id && $form_id !== $this->form_id) {
                        return $bridges;
                    }

                    if ($api && $api !== $this->api) {
                        return $bridges;
                    }

                    $bridges[] = $this;
                    return $bridges;
                },
                10,
                3
            );

            add_filter(
                'forms_bridge_bridge',
                function ($bridge, $name, $api) {
                    if ($bridge instanceof Form_Bridge) {
                        return $bridge;
                    }

                    $id = $api . '-' . $name;
                    if ($id !== $this->id) {
                        return $bridge;
                    }

                    return $this;
                },
                10,
                3
            );
        }
    }

    /**
     * Magic method to proxy public attributes to method getters.
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
            case 'form':
                return $this->form();
            case 'integration':
                return $this->integration();
            case 'backend':
                return $this->backend();
            case 'content_type':
                return $this->content_type();
            case 'credential':
                return $this->credential();
            case 'endpoint_schema':
                return $this->endpoint_schema();
            case 'workflow':
                return $this->workflow();
            default:
                if (is_wp_error($this->data)) {
                    return null;
                }

                return $this->data[$name] ?? null;
        }
    }

    /**
     * Retrives the bridge's backend instance.
     *
     * @return Http_Backend|null
     */
    protected function backend()
    {
        $backend_name = $this->data['backend'] ?? null;
        if (!$backend_name) {
            return;
        }

        return API::get_backend($backend_name);
    }

    /**
     * Retrives the bridge's form data.
     *
     * @return array|null
     */
    protected function form()
    {
        [$integration, $form_id] = explode(':', $this->form_id);
        return API::get_form_by_id($form_id, $integration);
    }

    /**
     * Retrives the bridge's integration name.
     *
     * @return string
     */
    protected function integration()
    {
        [$integration] = explode(':', $this->form_id);
        return $integration;
    }

    /**
     * Gets bridge's default body encoding schema.
     *
     * @return string|null
     */
    protected function content_type()
    {
        $backend = $this->backend();

        if (empty($backend)) {
            return;
        }

        return $backend->content_type;
    }

    /**
     * Bridge's endpoint fields schema getter.
     *
     * @return array
     */
    protected function endpoint_schema()
    {
        return [];
    }

    /**
     * Bridge's credential data getter.
     *
     * @return null
     */
    protected function credential() {}

    /**
     * Gets bridge's workflow instance.
     *
     * @return Workflow_Job|null;
     */
    protected function workflow()
    {
        return Workflow_Job::from_workflow(
            $this->data['workflow'] ?? [],
            $this->api
        );
    }

    /**
     * Bridge public submit method wrapped with hooks. Calls to the private
     * do_submit method.
     *
     * @param array $payload Form submission data.
     * @param array $attachments Form submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function submit($payload = [], $attachments = [])
    {
        add_filter(
            'http_bridge_request',
            static::class . '::filter_request',
            10,
            1
        );

        return $this->do_submit($payload, $attachments);
    }

    /**
     * Submits payload and attachments to the bridge's backend.
     *
     * @param array $payload Form submission data.
     * @param array $attachments Form submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    abstract protected function do_submit($payload, $attachments = []);

    /**
     * HTTP request args interceptor with automatic unsubscription.
     *
     * @param array $request HTTP request arguments.
     *
     * @return array
     */
    final public static function filter_request($request)
    {
        $request = static::do_filter_request($request);
        $headers = &$request['args']['headers'];

        if (count(static::$api_headers) > 0) {
            $api_headers = [];
            foreach ($headers as $header => $value) {
                if (in_array($header, static::$api_headers)) {
                    $api_headers[$header] = $value;
                }
            }

            $headers = $api_headers;
        }

        $no_content = in_array(
            $request['args']['method'],
            ['GET', 'DELETE'],
            true
        );

        if ($no_content) {
            if (isset($headers['Content-Type'])) {
                unset($headers['Content-Type']);
            } elseif (isset($headers['content-type'])) {
                unset($headers['content-type']);
            }
        }

        remove_filter(
            'http_bridge_request',
            static::class . '::filter_request',
            10,
            1
        );

        return $request;
    }

    /**
     * Filters HTTP request args just before it is sent.
     *
     * @param array $request Request arguments.
     *
     * @return array
     */
    protected static function do_filter_request($request)
    {
        // To be overwriten by descendants
        return $request;
    }

    /**
     * Returns a clone of the bridge instance with its data patched by
     * the partial array.
     *
     * @param array $partial Bridge data.
     *
     * @return Form_Bridge
     */
    public function patch($partial = [])
    {
        $data = array_merge($this->data, $partial);
        return new static($data, $this->api);
    }
}
