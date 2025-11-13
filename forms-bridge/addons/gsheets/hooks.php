<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( $addon !== 'gsheets' ) {
			return $schema;
		}

		$schema['properties']['endpoint']['default'] =
			'/v4/spreadsheets/{spreadsheet_id}';

		$schema['properties']['backend']['default'] = 'Sheets API';

		$schema['properties']['method']['enum']    = array( 'GET', 'POST', 'PUT' );
		$schema['properties']['method']['default'] = 'POST';

		$schema['properties']['tab'] = array(
			'description' => __( 'Name of the spreadsheet tab', 'forms-bridge' ),
			'type'        => 'string',
			'minLength'   => 1,
			'required'    => true,
			'default'     => 'Sheet1',
		);

		return $schema;
	},
	10,
	2
);

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( $addon !== 'gsheets' ) {
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
						'ref'   => '#credential',
						'name'  => 'oauth_url',
						'label' => __( 'Authorization URL', 'forms-bridge' ),
						'type'  => 'text',
						'value' => 'https://accounts.google.com/o/oauth2/v2',
					),
					array(
						'ref'      => '#credential',
						'name'     => 'client_id',
						'label'    => __( 'Client ID', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'      => '#credential',
						'name'     => 'client_secret',
						'label'    => __( 'Client secret', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'      => '#credential',
						'name'     => 'scope',
						'label'    => __( 'Scope', 'forms-bridge' ),
						'type'     => 'text',
						'value'    =>
							'https://www.googleapis.com/auth/drive.readonly https://www.googleapis.com/auth/spreadsheets',
						'required' => true,
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'endpoint',
						'label'    => __( 'Spreadsheet', 'forms-bridge' ),
						'type'     => 'select',
						'options'  => array(
							'endpoint' => '/drive/v3/files',
							'finger'   => array(
								'value' => 'files[].id',
								'label' => 'files[].name',
							),
						),
						'required' => true,
					),
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
					array(
						'ref'     => '#bridge',
						'name'    => 'tab',
						'label'   => __( 'Tab', 'forms-bridge' ),
						'type'    => 'text',
						'default' => '',
					),
					array(
						'ref'     => '#backend',
						'name'    => 'name',
						'default' => 'Sheets API',
					),
					array(
						'ref'   => '#backend',
						'name'  => 'base_url',
						'value' => 'https://sheets.googleapis.com',
					),
				),
				'backend'    => array(
					'name'     => 'Sheets API',
					'base_url' => 'https://sheets.googleapis.com',
					'headers'  => array(
						array(
							'name'  => 'Accept',
							'value' => 'application/json',
						),
					),
				),
				'bridge'     => array(
					'backend'  => 'Sheets API',
					'endpoint' => '',
					'tab'      => '',
				),
				'credential' => array(
					'name'          => '',
					'schema'        => 'Bearer',
					'oauth_url'     => 'https://accounts.google.com/o/oauth2/v2',
					'scope'         =>
						'https://www.googleapis.com/auth/drive.readonly https://www.googleapis.com/auth/spreadsheets',
					'client_id'     => '',
					'client_secret' => '',
					'access_token'  => '',
					'expires_at'    => 0,
					'refresh_token' => '',
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
		if ( strpos( $template_id, 'gsheets-' ) !== 0 ) {
			return $data;
		}

		$data['bridge']['endpoint'] =
			'/v4/spreadsheets/' . $data['bridge']['endpoint'];
		return $data;
	},
	10,
	2
);

add_filter(
	'http_bridge_oauth_url',
	function ( $url, $verb ) {
		if ( strpos( $url, 'accounts.google.com' ) === false ) {
			return $url;
		}

		if ( $verb === 'auth' ) {
			return $url;
		}

		return "https://oauth2.googleapis.com/{$verb}";
	},
	10,
	2
);
