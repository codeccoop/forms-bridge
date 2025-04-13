<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the Mailchimp API.
 */
class Mailchimp_Form_Bridge extends Rest_Form_Bridge
{
    /**
     * Handles allowed HTTP method.
     *
     * @var array
     */
    public const allowed_methods = ['POST'];
}
