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
     * Handles the form hook's settings data.
     *
     * @var array
     */
    private $data;

    /**
     * Handles form hook's api slug.
     *
     * @var string
     */
    protected $api;

    /**
     * Handles the form hook's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Form_Hook_Template';

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
            $api,
        );

        foreach ($template_files as $template_path) {
            if (!is_file($template_file) || !is_readable($template_path)) {
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

            if ($config) {
                static::$templates[] = new static::$template_class(
                    $template_file,
                    $config,
                    $api,
                );
            }
        }
    }

    /**
     * Gets a template instance by name.
     *
     * @param string $name Template name.
     *
     * @return Form_Hook_Template|null
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
     * Stores the hook's data as a private attribute.
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
                $value = $this->data[$name] ?? null;
        }

        return apply_filters("forms_bridge_hook_{$name}", $value, $this);
    }

    /**
     * Retrives the hook's backend instance.
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
     * Retrives the hook's form data.
     *
     * @return array|null
     */
    protected function form()
    {
        [$integration, $form_id] = explode(':', $this->form_id);
        return apply_filters('forms_bridge_form', null, $form_id, $integration);
    }

    /**
     * Retrives the hook's integration name.
     *
     * @return string
     */
    protected function integration()
    {
        [$integration] = explode(':', $this->form_id);
        return $integration;
    }

    /**
     * Gets form hook's default body encoding schema.
     *
     * @return string|null
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
