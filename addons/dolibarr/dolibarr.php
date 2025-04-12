<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once FORMS_BRIDGE_ADDONS_DIR . '/rest-api/rest-api.php';

require_once 'class-dolibarr-form-bridge.php';
require_once 'class-dolibarr-form-bridge-template.php';

require_once 'api-functions.php';

require_once 'country-codes.php';
// require_once 'state-codes.php';

/**
 * Dolibarr Addon class.
 */
class Dolibarr_Addon extends Rest_Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'Dolibarr';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'dolibarr';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Dolibarr_Form_Bridge';

    /**
     * Handles the addon's custom form bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Dolibarr_Form_Bridge_Template';
}

Dolibarr_Addon::setup();
