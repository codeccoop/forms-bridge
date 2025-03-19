<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the Dolibarr REST API.
 */
class Dolibarr_Form_Bridge extends Form_Bridge
{
    /**
     * Handles the form bridge's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Dolibarr_Form_Bridge_Template';

    /**
     * Parent getter interceptor to short circtuit API key access.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'api_key':
                return $this->api_key();
            default:
                return parent::__get($name);
        }
    }

    /**
     * Returns json as static bridge content type.
     *
     * @return string.
     */
    protected function content_type()
    {
        return 'application/json';
    }

    /**
     * Intercepts backend access and returns it from the api key.
     *
     * @return Http_Backend|null
     */
    protected function backend()
    {
        return $this->api_key()->backend;
    }

    /**
     * Bridge's API key private getter.
     *
     * @return Dolibarr_API_Key|null
     */
    private function api_key()
    {
        return apply_filters(
            'forms_bridge_dolibarr_api_key',
            null,
            $this->data['api_key'] ?? null
        );
    }

    /**
     * Performs an http request to Dolibarr's REST API.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function do_submit($payload, $attachments = [])
    {
        $api_key = $this->api_key();

        return $this->backend->post(
            $this->endpoint,
            $payload,
            ['DOLAPIKEY' => $api_key->key],
            $attachments
        );
    }
}
