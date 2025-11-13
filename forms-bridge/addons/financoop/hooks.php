<?php

use HTTP_BRIDGE\Credential;
use HTTP_BRIDGE\Backend;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( $addon !== 'financoop' ) {
			return $schema;
		}

		$schema['properties']['method']['enum'] = array( 'GET', 'POST' );
		return $schema;
	},
	10,
	2
);

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( $addon !== 'financoop' ) {
			return $defaults;
		}

		return wpct_plugin_merge_object(
			array(
				'fields'     => array(
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
					array(
						'ref'      => '#bridge/custom_fields[]',
						'name'     => 'campaign_id',
						'label'    => __( 'Campaign', 'forms-bridge' ),
						'type'     => 'select',
						'options'  => array(
							'endpoint' => '/api/campaign',
							'finger'   => array(
								'value' => '[].id',
								'label' => '[].name',
							),
						),
						'required' => true,
					),
					array(
						'ref'     => '#backend',
						'name'    => 'name',
						'default' => 'FinanCoop',
					),
					array(
						'ref'      => '#credential',
						'name'     => 'name',
						'label'    => __( 'Name', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'   => '#credential',
						'name'  => 'schema',
						'type'  => 'text',
						'value' => 'RPC',
					),
					array(
						'ref'      => '#credential',
						'name'     => 'database',
						'label'    => __( 'Database', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'      => '#credential',
						'name'     => 'client_id',
						'label'    => __( 'Username', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'      => '#credential',
						'name'     => 'client_secret',
						'label'    => __( 'Password', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
				),
				'bridge'     => array(
					'backend' => 'FinanCoop',
					'method'  => 'POST',
				),
				'backend'    => array(
					'headers' => array(
						array(
							'name'  => 'Accept',
							'value' => 'application/json',
						),
					),
				),
				'credential' => array(
					'name'          => '',
					'schema'        => 'RPC',
					'client_id'     => '',
					'client_secret' => '',
					'database'      => '',
				),
			),
			$defaults,
			$schema
		);
	},
	10,
	3
);

add_filter(
	'forms_bridge_template_data',
	function ( $data, $template_id ) {
		if ( strpos( $template_id, 'financoop-' ) !== 0 ) {
			return $data;
		}

		$index = array_search(
			'campaign_id',
			array_column( $data['bridge']['custom_fields'], 'name' )
		);

		if ( $index !== false ) {
			$campaign_id = $data['bridge']['custom_fields'][ $index ]['value'];

			$data['bridge']['endpoint'] = preg_replace(
				'/\{campaign_id\}/',
				$campaign_id,
				$data['bridge']['endpoint']
			);

			array_splice( $data['bridge']['custom_fields'], $index, 1 );
		} else {
			return new WP_Error(
				'invalid_fields',
				__(
					'Financoop template requireds the field $campaign_id',
					'forms-bridge'
				),
				array( 'status' => 400 )
			);
		}

		$endpoint = implode(
			'/',
			array_slice( explode( '/', $data['bridge']['endpoint'] ), 0, 4 )
		);

		$data['backend']['credential'] = $data['credential']['name'];

		Backend::temp_registration( $data['backend'] );
		Credential::temp_registration( $data['credential'] );

		$addon    = FBAPI::get_addon( 'financoop' );
		$response = $addon->fetch(
			$endpoint,
			$data['backend']['name'],
			$data['credential']['name']
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'financoop_api_error',
				__( 'Can\'t fetch campaign data', 'forms-bridge' ),
				array( 'status' => 500 )
			);
		}

		$campaign    = $response['data'];
		$field_names = array_column( $data['form']['fields'], 'name' );

		$index = array_search( 'donation_amount', $field_names );
		if ( $index !== false ) {
			$field = &$data['form']['fields'][ $index ];

			$min = $campaign['minimal_donation_amount'];
			if ( ! empty( $min ) ) {
				$field['min']     = $min;
				$field['default'] = $min;
			}
		}

		$index = array_search( 'loan_amount', $field_names );
		if ( $index !== false ) {
			$field = &$data['form']['fields'][ $index ];

			$min = $campaign['minimal_loan_amount'];
			if ( ! empty( $min ) ) {
				$field['min']     = $min;
				$field['default'] = $min;
			}

			$max = $campaign['maximal_loan_amount'];
			if ( ! empty( $max ) ) {
				$field['max'] = $max;
			}
		}

		$index = array_search( 'ordered_parts', $field_names );
		if ( $index !== false ) {
			$field = &$data['form']['fields'][ $index ];

			$min = $campaign['minimal_subscription_amount'];
			if ( ! empty( $min ) ) {
				$field['min']     = $min;
				$field['step']    = $min;
				$field['default'] = $min;
			}

			$max = $campaign['maximal_subscription_amount'];
			if ( ! empty( $max ) ) {
				$field['max'] = $max;
			}
		}

		return $data;
	},
	10,
	2
);
