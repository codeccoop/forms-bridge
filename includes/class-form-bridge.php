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

    public static $schema = [
        'type' => 'object',
        'additionalProperties' => false,
        'properties' => [
            'name' => ['type' => 'string'],
            'form_id' => ['type' => 'string'],
            'backend' => ['type' => 'string'],
            'credential' => ['type' => 'string'],
            'custom_fields' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'value' => ['type' => 'string'],
                    ],
                    'additionalProperties' => false,
                    'required' => ['name', 'value'],
                ],
            ],
            'mutations' => [
                'type' => 'array',
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'from' => ['type' => 'string'],
                            'to' => ['type' => 'string'],
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
            ],
            'workflow' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
            'is_valid' => ['type' => 'boolean'],
        ],
        'required' => [
            'name',
            'form_id',
            'backend',
            'constants',
            'mutations',
            'workflow',
            'is_valid',
        ],
    ];

    /**
     * Handles the form bridge's settings data.
     *
     * @var array
     */
    protected $data;

    /**
     * Handles form bridge's api slug.
     *
     * @var string
     */
    protected $api;

    /**
     * Stores the form bridge's data as a private attribute.
     */
    public function __construct($data, $api)
    {
        $this->api = $api;
        $this->data = $data;
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
            case 'api_schema':
                return $this->api_schema();
            case 'workflow':
                return $this->workflow();
            default:
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

        return apply_filters('http_bridge_backend', null, $backend_name);
    }

    /**
     * Retrives the bridge's form data.
     *
     * @return array|null
     */
    protected function form()
    {
        [$integration, $form_id] = explode(':', $this->form_id);
        return apply_filters('forms_bridge_form', null, $form_id, $integration);
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
     * Gets bridge's endpoint fields schema.
     *
     * @return array<array>
     */
    protected function api_schema()
    {
        return [];
    }

    /**
     * Gets bridge's backend credential data.
     *
     * @return array|null
     */
    protected function credential()
    {
        return;
    }

    /**
     * Gets bridge's workflow instnace.
     *
     * @return Workflow_Job|null;
     */
    protected function workflow()
    {
        return Workflow_Job::from_workflow($this->data['workflow'] ?? []);
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
    public function submit($payload, $attachments = [])
    {
        do_action(
            'forms_bridge_before_bridge_submit',
            $this,
            $payload,
            $attachments
        );
        $response = $this->do_submit($payload, $attachments);

        if (is_wp_error($response)) {
            do_action(
                'forms_bridge_bridge_submit_error',
                $this,
                $response,
                $payload,
                $attachments
            );
        } else {
            do_action(
                'forms_bridge_after_bridge_submit',
                $this,
                $response,
                $payload,
                $attachments
            );
        }

        return $response;
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

        if (empty($data['name']) || $data['name'] === $this->name) {
            $data['name'] = 'bridge-' . time();
        }

        return new static($data, $this->api);
    }
}
