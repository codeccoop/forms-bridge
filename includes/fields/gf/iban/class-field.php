<?php

namespace WPCT_ERP_FORMS\GF\Fields\Iban;

use WPCT_ERP_FORMS\Field as BaseField;
use GFAddOn;

require_once 'class-addon.php';
require_once 'class-gf-field.php';

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
