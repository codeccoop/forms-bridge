<?php

use FORMS_BRIDGE\Addon;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$setting_names = array(
	'bigin',
	'brevo',
	'dolibarr',
	'financoop',
	'gsheets',
	'holded',
	'listmonk',
	'mailchimp',
	'odoo',
	'rest-api',
	'zoho',
);

$credentials = array();

foreach ( $setting_names as $setting_name ) {
	$option = 'forms-bridge_' . $setting_name;

	$data = get_option( $option, array() );

	if ( $setting_name === 'rest-api' ) {
		$addon = Addon::addon( 'rest' );
	} else {
		$addon = Addon::addon( $setting_name );
	}

	$data['title'] = $addon::title;

	if ( ! isset( $data['bridges'] ) ) {
		$data['bridges'] = array();
	}

	$backends = array();
	foreach ( $data['bridges'] as &$bridge_data ) {
		$workflow = $bridge_data['workflow'] ?? array();
		for ( $i = 0; $i < count( $workflow ); $i++ ) {
			$job_name = $workflow[ $i ];

			if ( strpos( $job_name, 'forms-bridge-' ) === 0 ) {
				$job_name = substr( $job_name, 13 );
			} elseif ( strpos( $job_name, $setting_name ) === 0 ) {
				$job_name = substr( $job_name, strlen( $setting_name ) + 1 );
			}

			$bridge_data['workflow'][ $i ] = $job_name;
		}

		$credential   = $bridge_data['credential'] ?? null;
		$bridge_class = $addon::bridge_class;
		$bridge_data  = wpct_plugin_sanitize_with_schema(
			$bridge_data,
			$bridge_class::schema()
		);

		if ( is_wp_error( $bridge_data ) ) {
			continue;
		}

		$bridge_data['is_valid'] =
			isset( $bridge_data['form_id'] ) &&
			$bridge_data['form_id'] &&
			isset( $bridge_data['backend'] ) &&
			$bridge_data['backend'] &&
			isset( $bridge_data['method'] ) &&
			$bridge_data['method'] &&
			isset( $bridge_data['endpoint'] ) &&
			$bridge_data['endpoint'];

		if ( $backend = $bridge_data['backend'] ?? null ) {
			if ( ! isset( $backends[ $backend ] ) ) {
				$backends[ $backend ] = $credential;
			}
		}
	}

	if ( $option === 'forms-bridge_listmonk' ) {
		foreach ( $backends as $name => $credential ) {
			$backend = FBAPI::get_backend( $name );
			if ( ! $backend ) {
				continue;
			}

			$headers = $backend->headers;
			if ( isset( $headers['api_user'], $headers['token'] ) ) {
				$backend_data = array(
					'name'     => $backend->name,
					'base_url' => $backend->base_url,
					'headers'  => array(),
				);

				$credential_data = array(
					'name'          => $credential ?: $backend->name,
					'schema'        => 'Token',
					'client_id'     => $headers['api_user'],
					'client_secret' => $headers['token'],
				);

				unset( $headers['api_user'] );
				unset( $headers['token'] );

				foreach ( $headers as $name => $value ) {
					$backend_data['headers'][] = array(
						'name'  => $name,
						'value' => $value,
					);
				}

				$backend_data['credential'] = $credential_data['name'];
				FBAPI::save_backend( $backend_data );
				$credentials[] = $credential_data;
			}
		}
	} elseif ( $option === 'forms-bridge_mailchimp' ) {
		foreach ( $backends as $name => $credential ) {
			$backend = FBAPI::get_backend( $name );
			if ( ! $backend ) {
				continue;
			}

			$headers = $backend->headers;
			if ( isset( $headers['api-key'] ) ) {
				$backend_data = array(
					'name'     => $backend->name,
					'base_url' => $backend->base_url,
					'headers'  => array(),
				);

				$credential_data = array(
					'name'          => $credential ?: $backend->name,
					'schema'        => 'Basic',
					'client_id'     => 'forms-bridge',
					'client_secret' => $headers['api-key'],
				);

				unset( $headers['api-key'] );

				foreach ( $headers as $name => $value ) {
					$backend_data['headers'][] = array(
						'name'  => $name,
						'value' => $value,
					);
				}

				$backend_data['credential'] = $credential_data['name'];
				FBAPI::save_backend( $backend_data );
				$credentials[] = $credential_data;
			}
		}
	} elseif ( $option === 'forms-bridge_financoop' ) {
		foreach ( $backends as $name => $credential ) {
			$backend = FBAPI::get_backend( $name );
			if ( ! $backend ) {
				continue;
			}

			$headers = $backend->headers;
			if (
				isset(
					$headers['X-Odoo-Db'],
					$headers['X-Odoo-Username'],
					$headers['X-Odoo-Api-Key']
				)
			) {
				$backend_data = array(
					'name'     => $backend->name,
					'base_url' => $backend->base_url,
					'headers'  => array(),
				);

				$credential_data = array(
					'name'          => $credential ?: $backend->name,
					'schema'        => 'RPC',
					'client_id'     => $headers['X-Odoo-Username'],
					'client_secret' => $headers['X-Odoo-Api-Key'],
					'database'      => $headers['X-Odoo-Db'],
				);

				unset( $headers['X-Odoo-Db'] );
				unset( $headers['X-Odoo-Username'] );
				unset( $headers['X-Odoo-Api-Key'] );

				foreach ( $headers as $name => $value ) {
					$backend_data['headers'][] = array(
						'name'  => $name,
						'value' => $value,
					);
				}

				$backend_data['credential'] = $credential_data['name'];
				FBAPI::save_backend( $backend_data );
				$credentials[] = $credential_data;
			}
		}
	} elseif ( $option === 'forms-bridge_odoo' ) {
		foreach ( $data['credentials'] ?? array() as $credential ) {
			$credentials[] = array(
				'name'          => $credential['name'],
				'schema'        => 'RPC',
				'client_id'     => $credential['user'],
				'client_secret' => $credential['password'],
				'database'      => $credential['database'],
			);
		}

		unset( $data['credentials'] );

		foreach ( $backends as $name => $credential ) {
			$backend = FBAPI::get_backend( $name );
			if ( ! $backend ) {
				continue;
			}

			$backend_data               = $backend->data();
			$backend_data['credential'] = $credential;
			FBAPI::save_backend( $backend_data );
		}
	} elseif ( $option === 'forms-bridge_zoho' ) {
		unset( $data['credentials'] );
	} elseif ( $option === 'forms-bridge_bigin' ) {
		unset( $data['credentials'] );
	} elseif ( $option === 'forms-bridge_gsheets' ) {
		unset( $data['credentials'] );
	}

	update_option( $option, $data );
}

$rest = get_option( 'forms-bridge_rest-api' );
add_option( 'forms-bridge_rest', $rest );
delete_option( 'forms-bridge_rest-api' );

$registry         = get_option( 'forms_bridge_addons' );
$registry['rest'] = $registry['rest-api'];
unset( $registry['rest-api'] );
update_option( 'forms_bridge_addons', $registry );

$http                = get_option( 'http-bridge_general' );
$http['credentials'] = $credentials;
update_option( 'http-bridge_general', $http );
