<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the Bigin API protocol.
 */
class Bigin_Form_Bridge extends Zoho_Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    public const addon = 'bigin';

    /**
     * Handles the zoho oauth service name.
     *
     * @var string
     */
    protected const zoho_oauth_service = 'ZohoBigin';
}
