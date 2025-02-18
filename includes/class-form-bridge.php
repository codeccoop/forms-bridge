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
     * Handles the form bridge's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Form_Bridge_Template';

    /**
     * Handles available template instances.
     *
     * @var array
     */
    private static $templates = [];

    /**
     * Loads template configs from a given directory path.Allowed file formats
     * are php and json.
     *
     * @param string $templates_path Source templates directory path.
     * @param string $api API name.
     */
    final public static function load_templates($templates_path, $api)
    {
        if (!is_dir($templates_path)) {
            $res = mkdir($templates_path);
            if (!$res) {
                return;
            }
        }

        if (!is_readable($templates_path)) {
            return;
        }

        $template_files = apply_filters(
            'forms_bridge_template_files',
            array_map(function ($template_file) use ($templates_path) {
                return $templates_path . '/' . $template_file;
            }, array_diff(scandir($templates_path), ['.', '..'])),
            $api
        );

        foreach ($template_files as $template_path) {
            if (!is_file($template_path) || !is_readable($template_path)) {
                continue;
            }

            $template_file = basename($template_path);
            $ext = pathinfo($template_file)['extension'];

            $config = null;
            if ($ext === 'php') {
                $config = include $template_path;
            } elseif ($ext === 'json') {
                $content = file_get_contents($template_path);
                $config = json_decode($content, true);
            }

            if (is_array($config)) {
                static::$templates[] = new static::$template_class(
                    $template_file,
                    $config,
                    $api
                );
            }
        }
    }

    /**
     * Gets a template instance by name.
     *
     * @param string $name Template name.
     *
     * @return Form_Bridge_Template|null
     */
    final public static function get_template($name)
    {
        foreach (static::$templates as $template) {
            if ($template->name === $name) {
                return $template;
            }
        }
    }

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
        return $this->backend()->content_type;
    }

    /**
     * Submits payload and attachments to the bridge's backend.
     *
     * @param array $payload Form submission data.
     * @param array $attachments Form submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function submit($payload, $attachments = [])
    {
        do_action('forms_bridge_before_submit', $payload, $attachments, $this);

        $response = $this->do_submit($payload, $attachments);

        if (is_wp_error($response)) {
            do_action('forms_bridge_submit_error', $response, $this);
        } else {
            do_action('forms_bridge_submit', $response, $this);
        }

        return $response;
    }

    /**
     * Submits submission to the backend.
     *
     * @param array $payload Submission data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    abstract protected function do_submit($payload, $attachments);

    /**
     * Apply cast pipes to data.
     *
     * @param array $data Array of data.
     *
     * @return array Data modified by the bridge's pipes.
     */
    final public function apply_pipes($data)
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
                try {
                    return wp_json_encode($value, JSON_UNESCAPED_UNICODE);
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
