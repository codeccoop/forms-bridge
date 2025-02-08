<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form hook implamentation for the FinanCoop REST API.
 */
class Finan_Coop_Form_Hook extends Form_Hook
{
    /**
     * Handles the form hook's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Finan_Coop_Form_Hook_Template';

    /**
     * Inherits the parent constructor and sets its api name.
     *
     * @param array $data Hook data.
     */
    public function __construct($data)
    {
        $this->api = 'financoop';

        parent::__construct(
            array_merge($data, [
                'method' => 'POST',
            ])
        );
    }
}
