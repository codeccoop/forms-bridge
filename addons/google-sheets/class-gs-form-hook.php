<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Google_Sheets_Form_Hook extends Form_Hook
{
    public function __construct($data)
    {
        parent::__construct($data);
        $this->api = 'google-sheets-api';
    }
}
