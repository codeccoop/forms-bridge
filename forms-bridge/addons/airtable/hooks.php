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
						'ref'         => '#credential',
						'name'        => 'access_token',
						'label'       => __( 'Access token', 'forms-bridge' ),
						'description' => __(
							'Register your Personal Access Token in the <a target="_blank" href="https://airtable.com/create/tokens">Airtable Builder Hub</a>',
							'forms-bridge'
						),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'endpoint',
						'label'    => __( 'Table', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
						'options'  => array(
							'endpoint' => '/v0/meta/bases',
							'finger'   => array(
								'value' => 'tables[].endpoint',
								'label' => 'tables[].name',
							),
						),
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
				),
				'bridge'     => array(
					'backend'  => 'Airtable API',
					'endpoint' => '',
				),
				'credential' => array(
					'name'         => '',
					'schema'       => 'Bearer',
					'access_token' => '',
					'expires_at'   => 0,
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
	'http_bridge_oauth_url',
	function ( $url, $verb ) {
		if ( false === strstr( $url, 'airtable.com' ) ) {
			return $url;
		}

		if ( 'auth' === $verb ) {
			$url .= 'orize';
		}

		return $url;
	},
	10,
	2
);
