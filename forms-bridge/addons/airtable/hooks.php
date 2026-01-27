<?php
/**
 * Airtable addon hooks
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( 'airtable' !== $addon ) {
			return $schema;
		}

		$schema['properties']['endpoint']['default'] = '/{base_id}/{table_name}';

		$schema['properties']['backend']['const'] = 'Airtable API';

		$schema['properties']['method']['enum']    = array( 'GET', 'POST', 'PUT', 'PATCH' );
		$schema['properties']['method']['default'] = 'POST';

		return $schema;
	},
	10,
	2
);

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'airtable' !== $addon ) {
			return $defaults;
		}

		$defaults = wpct_plugin_merge_object(
			array(
				'fields'     => array(
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
						'value' => 'Bearer',
					),
					array(
						'ref'      => '#credential',
						'name'     => 'api_key',
						'label'    => __( 'API Key', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'         => '#bridge',
						'name'        => 'endpoint',
						'label'       => __( 'Endpoint', 'forms-bridge' ),
						'description' => __( 'Format: base_id/table_name', 'forms-bridge' ),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
					array(
						'ref'     => '#backend',
						'name'    => 'name',
						'default' => 'Airtable API',
					),
					array(
						'ref'   => '#backend',
						'name'  => 'base_url',
						'value' => 'https://api.airtable.com',
					),
				),
				'backend'    => array(
					'name'     => 'Airtable API',
					'base_url' => 'https://api.airtable.com',
					'headers'  => array(
						array(
							'name'  => 'Authorization',
							'value' => 'Bearer {api_key}',
						),
						array(
							'name'  => 'Content-Type',
							'value' => 'application/json',
						),
					),
				),
				'bridge'     => array(
					'backend'  => 'Airtable API',
					'endpoint' => '',
				),
				'credential' => array(
					'name'    => '',
					'schema'  => 'Bearer',
					'api_key' => '',
				),
			),
			$defaults,
			$schema
		);

		return $defaults;
	},
	10,
	3
);

add_filter(
	'forms_bridge_template_data',
	function ( $data, $template_id ) {
		if ( strpos( $template_id, 'airtable-' ) !== 0 ) {
			return $data;
		}

		// Ensure endpoint format is correct for Airtable
		if ( ! empty( $data['bridge']['endpoint'] ) && strpos( $data['bridge']['endpoint'], '/' ) === false ) {
			$data['bridge']['endpoint'] = '/' . $data['bridge']['endpoint'];
		}
		return $data;
	},
	10,
	2
);
