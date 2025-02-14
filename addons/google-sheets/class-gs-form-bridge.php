<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implementation for the Google Sheets service.
 */
class Google_Sheets_Form_Bridge extends Form_Bridge
{
    /**
     * Handles the form bridge's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Google_Sheets_Form_Bridge_Template';
}
