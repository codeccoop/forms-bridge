<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( $addon !== 'nextcloud' ) {
			return $schema;
		}

		$schema['properties']['endpoint']['title']       = __(
			'Filepath',
			'forms-bridge'
		);
		$schema['properties']['endpoint']['description'] = __(
			'Path to the CSV file from the root of your nextcloud file system directory',
			'forms-bridge'
		);
		$schema['properties']['endpoint']['pattern']     = '.+\.csv$';

		$schema['properties']['method']['enum']    = array( 'PUT' );
		$schema['properties']['method']['default'] = 'PUT';

		return $schema;
	},
	10,
	2
);

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( $addon !== 'nextcloud' ) {
			return $defaults;
		}

		return wpct_plugin_merge_object(
			array(
				'fields'     => array(
					array(
						'ref'     => '#backend',
						'name'    => 'name',
						'default' => 'Nextcloud',
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'endpoint',
						'label'    => __( 'Filepath', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
						'pattern'  => '.+.csv$',
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'method',
						'label'    => __( 'Method', 'forms-bridge' ),
						'type'     => 'text',
						'value'    => 'PUT',
						'required' => true,
					),
					array(
						'ref'     => '#bridge',
						'name'    => 'endpoint',
						'label'   => __( 'Filepath', 'forms-bridge' ),
						'pattern' => '.+\.csv$',
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
						'value' => 'Basic',
					),
					array(
						'ref'         => '#credential',
						'name'        => 'client_id',
						'label'       => __( 'User login', 'forms-bridge' ),
						'description' => __(
							'Either, a user name or email',
							'forms-bridge'
						),
						'type'        => 'text',
						'required'    => true,
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
					'backend' => 'Nextcloud',
				),
				'backend'    => array(
					'name'    => 'Nextcloud',
					'headers' => array(
						array(
							'name'  => 'Content-Type',
							'value' => 'application/octet-stream',
						),
					),
				),
				'credential' => array(
					'name'          => '',
					'schema'        => 'Basic',
					'client_id'     => '',
					'client_secret' => '',
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
		if ( strpos( $template_id, 'nextcloud-' ) !== 0 ) {
			return $data;
		}

		if ( ! preg_match( '/\.csv$/i', $data['bridge']['endpoint'] ) ) {
			$data['bridge']['endpoint'] .= '.csv';
		}

		return $data;
	},
	10,
	2
);
