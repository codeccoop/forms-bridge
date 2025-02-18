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
     * Handles allowed HTTP method.
     *
     * @var array
     */
    protected const allowed_methods = ['GET', 'POST', 'PUT', 'DELETE'];

    /**
     * Handles the form bridge's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Rest_Form_Bridge_Template';

    /**
     * Performs an http request to backend's REST API.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function do_submit($payload, $attachments = [])
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
}
