<?php

namespace FORMS_BRIDGE;

use FBAPI;
use WP_Error;

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
        return apply_filters(
            'forsm_bridge_bridge_schema',
            [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'form-bridge-schema',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'name' => __('Name', 'forms-bridge'),
                        'description' => __(
                            'Unique name of the bridge',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'form_id' => [
                        'name' => __('Form', 'forms-bridge'),
                        'description' => __(
                            'Internal form id with integration prefix',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        // 'pattern' => '^\w+:\d+$',
                        'default' => '',
                    ],
                    'backend' => [
                        'name' => __('Backend', 'forms-bridge'),
                        'description' => __('Backend name', 'forms-bridge'),
                        'type' => 'string',
                        'default' => '',
                    ],
                    // 'credential' => [
                    //     'description' => __('Credential name', 'forms-bridge'),
                    //     'type' => 'string',
                    //     'default' => '',
                    // ],
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
                                    'type' => ['string', 'integer', 'number'],
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
                    // 'form_id',
                    // 'backend',
                    'custom_fields',
                    'mutations',
                    'workflow',
                    'is_valid',
                    'enabled',
                ],
                'additionalProperties' => false,
            ],
            static::addon
        );
    }

    /**
     * Handles the form bridge's settings data.
     *
     * @var array
     */
    protected $data;

    protected $id;

    /**
     * Handles form bridge's addon slug.
     *
     * @var string
     */
    public const addon = 'forms-bridge';

    /**
     * Stores the form bridge's data as a private attribute.
     */
    public function __construct($data)
    {
        $this->data = wpct_plugin_sanitize_with_schema($data, static::schema());

        if ($this->is_valid) {
            $this->id = static::addon . '-' . $data['name'];
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
            case 'addon':
                return static::addon;
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
            case 'is_valid':
                return !is_wp_error($this->data) &&
                    $this->data['is_valid'] &&
                    Addon::addon(static::addon) !== null;
            default:
                if (!$this->is_valid) {
                    return;
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
        if (!$this->is_valid) {
            return;
        }

        return FBAPI::get_backend($this->data['backend']);
    }

    /**
     * Retrives the bridge's form data.
     *
     * @return array|null
     */
    protected function form()
    {
        $form_id = $this->form_id;
        if (!$form_id) {
            return;
        }

        [$integration, $form_id] = explode(':', $form_id);
        return FBAPI::get_form_by_id($form_id, $integration);
    }

    /**
     * Retrives the bridge's integration name.
     *
     * @return string
     */
    protected function integration()
    {
        $form_id = $this->form_id;
        if (!$form_id) {
            return;
        }

        [$integration] = explode(':', $form_id);
        return $integration;
    }

    /**
     * Gets bridge's default body encoding schema.
     *
     * @return string|null
     */
    protected function content_type()
    {
        if (!$this->is_valid) {
            return;
        }

        $backend = FBAPI::get_backend($this->data['backend']);
        if (!$backend) {
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
        if (!$this->is_valid) {
            return [];
        }

        return Job::from_workflow($this->data['workflow'], static::addon);
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
        if (!$this->is_valid) {
            return new WP_Error(
                'invalid_bridge',
                'Bridge has invalid settings'
            );
        }

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
     * Returns a clone of the bridge instance with its data patched by
     * the partial array.
     *
     * @param array $partial Bridge data.
     *
     * @return Form_Bridge
     */
    public function patch($partial = [])
    {
        if (!$this->is_valid) {
            return $this;
        }

        $data = array_merge($this->data, $partial);
        return new static($data, static::addon);
    }

    public function save()
    {
        if (!$this->is_valid) {
            return false;
        }

        $setting = Settings_Store::setting(static::addon);
        if (!$setting) {
            return false;
        }

        $bridges = $setting->bridges ?: [];

        $index = array_search($this->name, array_column($bridges, 'name'));

        if ($index === false) {
            $bridges[] = $this->data;
        } else {
            $bridges[$index] = $this->data;
        }

        $setting->bridges = $bridges;

        return true;
    }

    public function delete()
    {
        if (!$this->is_valid) {
            return false;
        }

        $setting = Settings_Store::setting(static::addon);
        if (!$setting) {
            return false;
        }

        $bridges = $setting->bridges ?: [];

        $index = array_search($this->name, array_column($bridges, 'name'));

        if ($index === false) {
            return false;
        }

        array_splice($bridges, $index, 1);
        $setting->bridges = $bridges;

        return true;
    }
}
