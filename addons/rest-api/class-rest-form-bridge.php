<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

use WP_Error;

/**
 * Form bridge implamentation for the REST API protocol.
 */
class Rest_Form_Bridge extends Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'rest-api';

    /**
     * Handles allowed HTTP method.
     *
     * @var array
     */
    public const allowed_methods = ['GET', 'POST', 'PUT', 'DELETE'];

    /**
     * Performs an http request to backend's REST API.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    protected function do_submit($payload, $attachments = [])
    {
        if (!in_array($this->method, self::allowed_methods, true)) {
            return new WP_Error(
                'method_not_allowed',
                sprintf(
                    /* translators: %s: method name */
                    __('HTTP method %s is not allowed', 'forms-bridge'),
                    sanitize_text_field($this->method)
                ),
                ['method' => $this->method]
            );
        }

        $backend = $this->backend;
        $method_fn = strtolower($this->method);

        return $backend->$method_fn(
            $this->endpoint,
            $payload,
            [],
            $attachments
        );
    }

    public static function schema()
    {
        $schema = parent::schema();
        $schema['properties']['method'] = [
            'description' => __('HTTP method', 'forms-bridge'),
            'type' => 'string',
            'enum' => self::allowed_methods,
        ];

        $schema['required'][] = 'method';

        $schema['properties']['endpoint'] = [
            'description' => __('HTTP API endpoint', 'forms-bridge'),
            'type' => 'string',
        ];

        $schema['required'][] = 'endpoint';

        return $schema;
    }
}
