<?php

namespace WPCT_ERP_FORMS\GF\Fields\Iban;

use WPCT_ERP_FORMS\Abstract\Field as BaseField;

use GFAddOn;

require_once 'class-addon.php';
require_once 'class-field.php';

class FieldAdapter extends BaseField
{
    public function __construct()
    {
        add_action('gform_loaded', [$this, 'register']);
    }

    public function register()
    {
        if (!method_exists('GFForms', 'include_addon_framework')) return;
        GFAddOn::register(Addon::class);
    }

    public function init()
    {
    }
}
