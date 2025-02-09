<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form hook implamentation for the REST API protocol.
 */
class Rest_Form_Hook extends Form_Hook
{
    /**
     * Handles the form hook's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Rest_Form_Hook_Template';
}
