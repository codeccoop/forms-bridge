<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( $addon !== 'dolibarr' ) {
			return $schema;
		}

		unset( $schema['properties']['credential'] );
		return $schema;
	},
	10,
	2
);

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( $addon !== 'dolibarr' ) {
			return $defaults;
		}

		return wpct_plugin_merge_object(
			array(
				'fields'  => array(
					array(
						'ref'     => '#backend',
						'name'    => 'name',
						'default' => 'Dolibarr',
					),
					array(
						'ref'      => '#backend/headers[]',
						'name'     => 'DOLAPIKEY',
						'label'    => __( 'API key', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
				),
				'backend' => array(
					'name'    => 'Dolibarr',
					'headers' => array(
						array(
							'name'  => 'Accept',
							'value' => 'application/json',
						),
					),
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
		if ( strpos( $template_id, 'dolibarr-' ) !== 0 ) {
			return $data;
		}

		$index = array_search(
			'no_email',
			array_column( $data['bridge']['custom_fields'], 'name' )
		);

		if ( $index !== false ) {
			$field          = &$data['bridge']['custom_fields'][ $index ];
			$field['value'] = $field['value'] ? '0' : '1';
		}

		$index = array_search(
			'fulldayevent',
			array_column( $data['bridge']['custom_fields'], 'name' )
		);

		if ( $index !== false ) {
			$data['form']['fields'] = array_filter(
				$data['form']['fields'],
				function ( $field ) {
					return ! in_array(
						$field['name'],
						array(
							'hour',
							'minute',
							__( 'Hour', 'forms-bridge' ),
							__( 'Minute', 'forms-bridge' ),
						),
						true
					);
				}
			);

			$index = array_search(
				'duration',
				array_column( $data['bridge']['custom_fields'], 'name' )
			);

			if ( $index !== false ) {
				array_splice( $data['bridge']['custom_fields'], $index, 1 );
			}
		}

		return $data;
	},
	10,
	2
);
