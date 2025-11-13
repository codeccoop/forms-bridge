<?php
/**
 * Database migration to version 3.0.4
 *
 * @package formsbridge
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Migration 3.0.4
 */
function forms_bridge_migration_304() {
	$setting_names = array( 'rest-api', 'odoo', 'financoop', 'google-sheets' );

	foreach ( $setting_names as $setting_name ) {
		$option = 'forms-bridge_' . $setting_name;

		$data = get_option( $option, array() );

		if ( ! isset( $data['bridges'] ) ) {
			continue;
		}

		foreach ( $data['bridges'] as &$bridge_data ) {
			$pipes = $bridge_data['pipes'] ?? array();
			unset( $bridge_data['pipes'] );

			if (
			! isset( $bridge_data['mappers'] ) ||
			! wp_is_numeric_array( $bridge_data['mappers'] )
			) {
				$bridge_data['mappers'] = $pipes;
			}
		}

		update_option( $option, $data );
	}
}

forms_bridge_migration_304();
