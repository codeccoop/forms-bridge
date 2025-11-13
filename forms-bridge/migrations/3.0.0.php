<?php
/**
 * Database migration to version 3.0.0
 *
 * @package formsbridge
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Migration 3.0.0
 */
function forms_bridge_migration_300() {
	$setting_names = array( 'rest-api', 'odoo', 'financoop', 'google-sheets' );

	foreach ( $setting_names as $setting_name ) {
		$option            = 'forms-bridge_' . $setting_name;
		$deprecated_option = $option . '-api';

		$data = get_option( $deprecated_option );
		if ( is_array( $data ) ) {
			update_option( $option, $data );
			delete_option( $deprecated_option );
		}

		$data = get_option( $option, array() );

		if ( isset( $data['form_hooks'] ) ) {
			$data['bridges'] = $data['form_hooks'];
			unset( $data['form_hooks'] );
			update_option( $option, $data );
		}
	}
}

forms_bridge_migration_300();
