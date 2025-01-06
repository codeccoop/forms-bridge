<?php

namespace FORMS_BRIDGE;

use TypeError;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form hook object.
 */
class Form_Hook
{
    /**
     * Handles form hook settings data.
     *
     * @var array $data.
     */
    protected $data;

    /**
     * Handles form hook's api slug.
     *
     * @var string $api;
     */
    protected $api;

    /**
     * Form hooks getter.
     *
     * @param string $integration Integration slug.
     * @param int $form_id Optional, ID of the target form. Current form is used if not especified.
     *
     * @return array $hooks Array with hooks.
     */
    public static function form_hooks($integration, $form_id = null)
    {
        // If no form id, check if there is a current form, otherwise returns an empty string.
        if (empty($form_id)) {
            $form = apply_filters(
                'forms_bridge_form',
                null,
                $integration,
                $form_id
            );
            if (!$form) {
                return [];
            }

            $form_id = $form['id'];
        }

        $_id = "{$integration}:{$form_id}";

        // Get available form hooks
        $form_hooks = Forms_Bridge::setting('rest-api')->form_hooks;

        // Filter form hooks by form id and returns the resulting array
        return array_map(
            static function ($hook_data) {
                return new Form_Hook($hook_data);
            },
            array_filter($form_hooks, static function ($hook_data) use ($_id) {
                return $hook_data['form_id'] === $_id;
            })
        );
    }

    /**
     * Binds the hook data and sets its protocol.
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->api = 'rest';
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
                $value = $this->api;
                break;
            case 'form':
                $value = $this->form();
                break;
            case 'integration':
                $value = $this->integration();
                break;
            case 'backend':
                $value = $this->backend();
                break;
            case 'content_type':
                $value = $this->content_type();
                break;
            default:
                $value = isset($this->data[$name]) ? $this->data[$name] : null;
        }

        return apply_filters("forms_bridge_hook_{$name}", $value, $this);
    }

    /**
     * Retrives the hook's backend instance.
     *
     * @return Http_Backend Backend instance.
     */
    private function backend()
    {
        return apply_filters(
            'http_bridge_backend',
            null,
            isset($this->data['backend']) ? $this->data['backend'] : null
        );
    }

    /**
     * Retrives the hook's form data.
     *
     * @return array Form data.
     */
    protected function form()
    {
        [$integration, $form_id] = explode(':', $this->form_id);
        return apply_filters('forms_bridge_form', null, $integration, $form_id);
    }

    /**
     * Retrives the hook's integration.
     *
     * @return string Integration slug.
     */
    protected function integration()
    {
        [$integration] = explode(':', $this->form_id);
        return $integration;
    }

    /**
     * Gets form hook's default body encoding schema.
     *
     * @return string|null Encoding schema.
     */
    protected function content_type()
    {
        return $this->backend()->content_type;
    }

    /**
     * Submits submission to the backend.
     *
     * @param array $submission Submission data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function submit($submission, $attachments = [])
    {
        $backend = $this->backend;
        $method = strtolower($this->method);

        // Exists if http method is unkown
        if (!in_array($method, ['get', 'post', 'put', 'delete'])) {
            return new WP_Error(
                'method_not_allowed',
                "HTTP method {$this->method} is not allowed",
                ['method' => $this->method]
            );
        }

        do_action(
            'forms_bridge_before_submit',
            $this->endpoint,
            $submission,
            $attachments,
            $this
        );
        $response = $backend->$method(
            $this->endpoint,
            $submission,
            [],
            $attachments
        );
        do_action('forms_bridge_after_submit', $response, $this->name, $this);
        return $response;
    }

    /**
     * Apply cast pipes to data.
     *
     * @param array $data Array of data.
     *
     * @return array Data modified by the hook's pipes.
     */
    public function apply_pipes($data)
    {
        $finger = new JSON_Finger($data);
        foreach ($this->pipes as $pipe) {
            extract($pipe);
            $value = $finger->get($from);
            $finger->unset($from);
            if ($cast !== 'null') {
                $finger->set($to, $this->cast($value, $cast));
            }
        }

        return $finger->data();
    }

    /**
     * Cast value to type.
     *
     * @param mixed $value Original value.
     * @param string $type Target type to cast value.
     *
     * @return mixed $value Casted value.
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
                try {
                    return json_encode($value, JSON_UNESCAPED_UNICODE);
                } catch (TypeError) {
                    return '';
                }
            case 'csv':
                return implode(',', (array) $value);
            case 'concat':
                return implode(' ', (array) $value);
            case 'null':
                return null;
            default:
                return (string) $value;
        }
    }
}
