<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package formsbridge-tests
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
// phpcs:disable WordPress.Security.EscapeOutput

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" .
		PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	add_filter(
		'doing_it_wrong_trigger_error',
		function ( $trigger, $function_name ) {
			return $trigger && '_load_textdomain_just_in_time' !== $function_name;
		},
		90,
		2
	);

	require dirname( __DIR__ ) . '/forms-bridge/forms-bridge.php';

	/* Integrations */
	require ABSPATH . 'wp-content/mu-plugins/contact-form-7/wp-contact-form-7.php';
	require ABSPATH . 'wp-content/mu-plugins/gravityforms/gravityforms.php';
	require ABSPATH . 'wp-content/mu-plugins/ninja-forms/ninja-forms.php';
	require ABSPATH . 'wp-content/mu-plugins/wpforms/wpforms.php';
	require ABSPATH . 'wp-content/mu-plugins/woocommerce/woocommerce.php';

	/* Plugin tests */
	require dirname( __DIR__ ) . '/forms-bridge/deps/plugin/tests/bootstrap.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
