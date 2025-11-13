<?php
/**
 * Database migration to version 4.0.7
 *
 * @package formsbridge
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Migration 4.0.7
 */
function forms_bridge_migration_407() {
	$http = get_option( 'http-bridge_general', array() ) ?: array(
		'backends'    => array(),
		'credentials' => array(),
	);

	$result = update_option( 'forms-bridge_http', $http );

	if ( $result ) {
		delete_option( 'http-bridge_general' );
	}
}

forms_bridge_migration_407();
