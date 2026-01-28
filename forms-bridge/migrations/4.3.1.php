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
 * Migration 4.3.1
 */
function forms_bridge_migration_431() {
	$http = get_option( 'forms-bridge_http', array() ) ?: array(
		'backends'    => array(),
		'credentials' => array(),
	);

	foreach ( $http['credentials'] as &$credential ) {
		if ( 'Bearer' === $credential['schema'] ) {
			$credential['schema'] = 'OAuth';
		}
	}

	update_option( 'forms-bridge_http', $http );
}
