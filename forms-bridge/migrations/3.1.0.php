<?php
/**
 * Database migration to version 3.1.0
 *
 * @package formsbridge
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Migration 3.1.0
 */
function forms_bridge_migration_310() {
	$setting_names = array(
		'rest-api',
		'dolibarr',
		'odoo',
		'financoop',
		'google-sheets',
		'zoho',
	);

	foreach ( $setting_names as $setting_name ) {
		$option = 'forms-bridge_' . $setting_name;

		$data = get_option( $option, array() );

		if ( ! isset( $data['bridges'] ) ) {
			continue;
		}

		foreach ( $data['bridges'] as &$bridge_data ) {
			if ( ! isset( $bridge_data['workflow'] ) ) {
				if ( ! empty( $bridge_data['template'] ) ) {
					$template = apply_filters(
						'forms_bridge_template',
						null,
						$bridge_data['template']
					);

					if ( $template ) {
						$bridge_data['workflow'] =
							$template->bridge['workflow'] ?? array();
					}
				}
			}

			$bridge_data['workflow'] = $bridge_data['workflow'] ?? array();

			if ( ! isset( $bridge_data['mutations'] ) ) {
				$mappers   = $bridge_data['mappers'] ?? array();
				$mutations = array( $mappers );
			} else {
				$mutations = $bridge_data['mutations'];
			}

			$count = count( $bridge_data['workflow'] );
			for ( $i = count( $mutations ); $i <= $count; $i++ ) {
				$mutations[] = array();
			}

			$bridge_data['mutations'] = $mutations;
			unset( $bridge_data['mappers'] );
		}

		update_option( $option, $data );
	}
}

forms_bridge_migration_310();
