<?php

use FORMS_BRIDGE\Addon;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_migration_400() {
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

		if ( 'rest-api' === $setting_name ) {
			$addon = Addon::addon( 'rest' );
		} else {
			$addon = Addon::addon( $setting_name );
		}

		$data['title'] = $addon::TITLE;

		if ( ! isset( $data['bridges'] ) ) {
			$data['bridges'] = array();
		}

		$backends = array();
		foreach ( $data['bridges'] as &$bridge_data ) {
			$workflow = $bridge_data['workflow'] ?? array();

			$l = count( $workflow );
			for ( $i = 0; $i < $l; $i++ ) {
				$job_name = $workflow[ $i ];

				if ( strpos( $job_name, 'forms-bridge-' ) === 0 ) {
					$job_name = substr( $job_name, 13 );
				} elseif ( strpos( $job_name, $setting_name ) === 0 ) {
					$job_name = substr( $job_name, strlen( $setting_name ) + 1 );
				}

				$bridge_data['workflow'][ $i ] = $job_name;
			}

			$credential   = $bridge_data['credential'] ?? null;
			$bridge_class = $addon::BRIDGE;
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

			$backend = $bridge_data['backend'] ?? null;
			if ( $backend ) {
				if ( ! isset( $backends[ $backend ] ) ) {
					$backends[ $backend ] = $credential;
				}
			}
		}

		if ( 'forms-bridge_listmonk' === $option ) {
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
		} elseif ( 'forms-bridge_mailchimp' === $option ) {
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
		} elseif ( 'forms-bridge_financoop' === $option ) {
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
		} elseif ( 'forms-bridge_odoo' === $option ) {
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
		} elseif ( 'forms-bridge_zoho' === $option ) {
			unset( $data['credentials'] );
		} elseif ( 'forms-bridge_bigin' === $option ) {
			unset( $data['credentials'] );
		} elseif ( 'forms-bridge_gsheets' === $option ) {
			unset( $data['credentials'] );
		}

		update_option( $option, $data );
	}

	$rest = get_option( 'forms-bridge_rest-api' );
	if ( $rest && is_array( $rest ) ) {
		add_option( 'forms-bridge_rest', $rest );
		delete_option( 'forms-bridge_rest-api' );
	}

	$registry = get_option( 'forms_bridge_addons' );
	if ( $registry && is_array( $registry ) ) {
		$registry['rest'] = $registry['rest-api'];
		unset( $registry['rest-api'] );
		update_option( 'forms_bridge_addons', $registry );
	}

	$http = get_option( 'http-bridge_general' );
	if ( $http && is_array( $http ) ) {
		$http['credentials'] = $credentials;
		update_option( 'http-bridge_general', $http );
	}
}

forms_bridge_migration_400();
