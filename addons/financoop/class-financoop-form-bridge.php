<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the FinanCoop REST API.
 */
class Finan_Coop_Form_Bridge extends Form_Bridge
{
    /**
     * Handles allowed HTTP method.
     *
     * @var array
     */
    protected const allowed_methods = ['POST'];

    /**
     * Handles the form bridge's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Finan_Coop_Form_Bridge_Template';

    /**
     * Performs an http request to Odoo REST API.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function do_submit($payload, $attachments = [])
    {
        $response = $this->backend->post($this->endpoint, $payload);
        $result = Odoo_Form_Bridge::rpc_response($response);

        if (isset($result['error'])) {
            return new WP_Error($result['status'], $result['error'], $payload);
        }

        return $result;
    }
}
