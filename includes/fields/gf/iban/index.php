<?php

namespace WPCT_ERP_FORMS\Fields\GF\Iban;

use WPCT_ERP_FORMS\Fields\BaseField;
use GFAddOn;

class Field extends BaseField
{
    public function register()
    {
        add_action('gform_loaded', [$this, '_register']);
    }

    private function _register()
    {
        if (!method_exists('GFForms', 'include_addon_framework')) return;
        require_once 'Addon.php';
        require_once 'Field.php';


        GFAddOn::register(Addon::class);
    }
}
