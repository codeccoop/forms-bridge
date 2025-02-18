<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the FinanCoop REST API.
 */
class Finan_Coop_Form_Bridge extends Rest_Form_Bridge
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
     * Inherits the parent constructor and sets its api name.
     *
     * @param array $data Form bridge data.
     * @param string $api Bridge API name.
     */
    public function __construct($data, $api)
    {
        parent::__construct(
            array_merge($data, [
                'method' => 'POST',
            ]),
            $api
        );
    }
}
