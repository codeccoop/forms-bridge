<?php
/**
 * Plugin Name:         Forms Bridge
 * Plugin URI:          https://formsbridge.codeccoop.org
 * Description:         Bridge your WordPress forms without code, add custom fields, use mappers, set up a workflow and make your data flow seamlessly to your backend
 * Author:              codeccoop
 * Author URI:          https://www.codeccoop.org
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         forms-bridge
 * Domain Path:         /languages
 * Version:             4.1.1
 * Requires PHP:        8.0
 * Requires at least:   6.7
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

define( 'FORMS_BRIDGE_INDEX', __FILE__ );
define( 'FORMS_BRIDGE_DIR', __DIR__ );
define( 'FORMS_BRIDGE_INTEGRATIONS_DIR', FORMS_BRIDGE_DIR . '/integrations' );
define( 'FORMS_BRIDGE_ADDONS_DIR', FORMS_BRIDGE_DIR . '/addons' );

/* Packages */
if ( ! class_exists( 'WPCT_PLUGIN\Plugin' ) ) {
	require_once __DIR__ . '/deps/plugin/class-plugin.php';
}

if ( ! class_exists( 'HTTP_BRIDGE\Backend' ) ) {
	require_once __DIR__ . '/deps/http/index.php';
}

/* Classes */
require_once __DIR__ . '/includes/class-api.php';
require_once __DIR__ . '/includes/class-json-finger.php';
require_once __DIR__ . '/includes/class-rest-settings-controller.php';
require_once __DIR__ . '/includes/class-settings-store.php';
require_once __DIR__ . '/includes/class-logger.php';
require_once __DIR__ . '/includes/class-menu.php';
require_once __DIR__ . '/includes/class-form-bridge.php';
require_once __DIR__ . '/includes/class-form-bridge-template.php';
require_once __DIR__ . '/includes/class-job.php';
require_once __DIR__ . '/includes/class-integration.php';
require_once __DIR__ . '/includes/class-addon.php';
require_once __DIR__ . '/includes/class-forms-bridge.php';

/* Post types */
require_once __DIR__ . '/post_types/job.php';
require_once __DIR__ . '/post_types/bridge-template.php';

/* Start the plugin */
FORMS_BRIDGE\Forms_Bridge::setup();
