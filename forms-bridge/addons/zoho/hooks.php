<?php
/**
 * Zoho addon hooks
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'zoho' !== $addon ) {
			return $defaults;
		}

		return wpct_plugin_merge_object(
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
						'value' => 'https://accounts.{region}/oauth/v2',
					),
					array(
						'ref'         => '#credential',
						'name'        => 'region',
						'label'       => __( 'Datacenter', 'forms-bridge' ),
						'description' => __(
							'Pick up your Zoho account datacenter. See the <a href="https://www.zoho.com/crm/developer/docs/api/v8/multi-dc.html">documentation</a> for more information',
							'forms-bridge'
						),
						'type'        => 'select',
						'options'     => array(
							array(
								'value' => 'zoho.com',
								'label' => 'zoho.com',
							),
							array(
								'value' => 'zoho.eu',
								'label' => 'zoho.eu',
							),
							array(
								'value' => 'zoho.in',
								'label' => 'zoho.in',
							),
							array(
								'value' => 'zoho.com.cn',
								'label' => 'zoho.com.cn',
							),
							array(
								'value' => 'zoho.com.au',
								'label' => 'zoho.com.au',
							),
							array(
								'value' => 'zoho.jp',
								'label' => 'zoho.jp',
							),
							array(
								'label' => 'zoho.sa',
								'value' => 'zoho.sa',
							),
						),
						'required'    => true,
					),
					array(
						'ref'         => '#credential',
						'name'        => 'client_id',
						'label'       => __( 'Client ID', 'forms-bridge' ),
						'description' => __(
							'Register your OAuth client on the <a href="https://accounts.zoho.com">Zoho API Console</a> and get its Client ID',
							'forms-bridge'
						),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'         => '#credential',
						'name'        => 'client_secret',
						'label'       => __( 'Client secret', 'forms-bridge' ),
						'description' => __(
							'Register your OAuth client on the <a href="https://accounts.zoho.com">Zoho API Console</a> and get its Client secret',
							'forms-bridge'
						),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'      => '#credential',
						'name'     => 'scope',
						'label'    => __( 'Scope', 'forms-bridge' ),
						'type'     => 'text',
						'value'    => 'ZohoCRM.modules.ALL,ZohoCRM.settings.layouts.READ,ZohoCRM.users.READ',
						'required' => true,
					),
					array(
						'ref'     => '#backend',
						'name'    => 'name',
						'default' => 'Zoho API',
					),
					array(
						'ref'         => '#backend',
						'name'        => 'base_url',
						'description' => __(
							'Pick up your Zoho account datacenter. See the <a href="https://www.zoho.com/crm/developer/docs/api/v8/multi-dc.html">documentation</a> for more information',
							'forms-bridge'
						),
						'type'        => 'select',
						'options'     => array(
							array(
								'label' => 'www.zohoapis.com',
								'value' => 'https://www.zohoapis.com',
							),
							array(
								'label' => 'www.zohoapis.eu',
								'value' => 'https://www.zohoapis.eu',
							),
							array(
								'label' => 'www.zohoapis.com.au',
								'value' => 'https://www.zohoapis.com.au',
							),
							array(
								'label' => 'www.zohoapis.in',
								'value' => 'https://www.zohoapis.in',
							),
							array(
								'label' => 'www.zohoapis.cn',
								'value' => 'https://www.zohoapis.cn',
							),
							array(
								'label' => 'www.zohoapis.jp',
								'value' => 'https://www.zohoapis.jp',
							),
							array(
								'label' => 'www.zohoapis.sa',
								'value' => 'https://www.zohoapis.sa',
							),
							array(
								'label' => 'www.zohoapis.ca',
								'value' => 'https://www.zohoapis.ca',
							),
						),
						'default'     => 'https://www.zohoapis.com',
						'required'    => true,
					),
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
				),
				'bridge'     => array(
					'backend'  => 'Zoho API',
					'endpoint' => '',
				),
				'credential' => array(
					'name'          => '',
					'schema'        => 'Bearer',
					'oauth_url'     => 'https://accounts.{region}/oauth/v2',
					'scope'         => 'ZohoCRM.modules.ALL,ZohoCRM.settings.layouts.READ,ZohoCRM.users.READ',
					'client_id'     => '',
					'client_secret' => '',
					'access_token'  => '',
					'expires_at'    => 0,
					'refresh_token' => '',
				),
				'backend'    => array(
					'base_url' => 'https://www.zohoapis.{region}',
					'headers'  => array(
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
		if ( 0 !== strpos( $template_id, 'zoho-' ) ) {
			return $data;
		}

		$region                          = $data['credential']['region'];
		$data['credential']['oauth_url'] = preg_replace(
			'/{region}/',
			$region,
			$data['credential']['oauth_url']
		);
		unset( $data['credential']['region'] );

		$index = array_search(
			'Tag',
			array_column( $data['bridge']['custom_fields'], 'name' ),
			true
		);

		if ( false !== $index ) {
			$field = &$data['bridge']['custom_fields'][ $index ];

			if ( ! empty( $field['value'] ) ) {
				$tags = array_filter(
					array_map( 'trim', explode( ',', strval( $field['value'] ) ) )
				);

				$l = count( $tags );
				for ( $i = 0; $i < $l; $i++ ) {
					$data['bridge']['custom_fields'][] = array(
						'name'  => "Tag[{$i}].name",
						'value' => $tags[ $i ],
					);
				}
			}

			array_splice( $data['bridge']['custom_fields'], $index, 1 );
		}

		$index = array_search(
			'All_day',
			array_column( $data['bridge']['custom_fields'], 'name' ),
			true
		);

		if ( false !== $index ) {
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
				array_column( $data['bridge']['custom_fields'], 'name' ),
				true
			);

			if ( false !== $index ) {
				array_splice( $data['bridge']['custom_fields'], $index, 1 );
			}
		}

		return $data;
	},
	10,
	2
);
