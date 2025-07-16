<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implementation for the Nextcloud JSON-RPC api.
 */
class Nextcloud_Form_Bridge extends Form_Bridge
{
    /**
     * Submits submission to the backend.
     *
     * @param array $payload Submission data.
     * @param array $attachments Submission attachments.
     *
     * @return array|WP_Error Http
     */
    public function submit($payload = [], $attachments = [])
    {
        return parent::submit($payload);
    }
}
