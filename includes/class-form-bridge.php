<?php

namespace FORMS_BRIDGE;

use TypeError;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge object.
 */
abstract class Form_Bridge
{
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
            return $backend_name;
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

        if ($data['name'] === $this->name) {
            $data['name'] = 'bridge-' . time();
        }

        return new static($data, $this->api);
    }

    /**
     * Apply cast mappers to data.
     *
     * @param array $data Array of data.
     *
     * @return array Data modified by the bridge's mappers.
     */
    final public function apply_mappers($data)
    {
        $finger = new JSON_Finger($data);
        foreach ($this->mappers as $mapper) {
            $is_valid =
                JSON_Finger::validate($mapper['from']) &&
                JSON_Finger::validate($mapper['to']);

            if (!$is_valid) {
                continue;
            }

            $isset = $finger->isset($mapper['from']);
            if (!$isset) {
                continue;
            }

            $value = $finger->get($mapper['from']);

            if ($mapper['cast'] !== 'copy') {
                $finger->unset($mapper['from']);
            }

            if ($mapper['cast'] !== 'null') {
                $finger->set(
                    $mapper['to'],
                    $this->cast($value, $mapper['cast'])
                );
            }
        }

        return $finger->data();
    }

    /**
     * Casts value to the given type.
     *
     * @param mixed $value Original value.
     * @param string $type Target type to cast value.
     *
     * @return mixed
     */
    private function cast($value, $type)
    {
        switch ($type) {
            case 'string':
                return (string) $value;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'json':
                return wp_json_encode($value, JSON_UNESCAPED_UNICODE);
            case 'csv':
                return implode(',', (array) $value);
            case 'concat':
                return implode(' ', (array) $value);
            case 'inherit':
                return $value;
            case 'copy':
                return $value;
            case 'null':
                return;
            default:
                return (string) $value;
        }
    }
}
