<?php

namespace WPCT_ERP_FORMS\Integrations;

use WPCT_ERP_FORMS\Integrations\WPCF7;
use WPCT_ERP_FORMS\Integrations\GF;

require_once 'Integration.php';

class WPCTIntegrationsRegistry
{
    public static $instances = [];
}

if (defined('WPCF7_VERSION')) {
    require_once 'wpcf7/index.php';
    add_action('plugins_loaded', function () {
        WPCTIntegrationsRegistry::$instances['wpcf7'] = new WPCF7();
    });
}

if (class_exists('GFForms')) {
    require_once 'gf/index.php';
    add_action('plugins_loaded', function () {
        WPCTIntegrationsRegistry::$instances['gf'] = new GF();
    });
}
