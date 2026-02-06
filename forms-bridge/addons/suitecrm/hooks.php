<?php
/**
 * SuiteCRM addon hooks and filters.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Customize the bridge schema for SuiteCRM.
 */
add_filter(
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( 'suitecrm' !== $addon ) {
			return $schema;
		}

		$schema['properties']['endpoint']['title']       = __( 'Module', 'forms-bridge' );
		$schema['properties']['endpoint']['description'] = __(
			'Name of the target SuiteCRM module (e.g., Contacts, Leads, Accounts)',
			'forms-bridge'
		);
		$schema['properties']['endpoint']['default']     = 'Contacts';

		$schema['properties']['method']['description'] = __( 'SuiteCRM API method name', 'forms-bridge' );
		$schema['properties']['method']['enum']        = array(
			'set_entry',
			'get_entry',
			'get_entry_list',
			'set_relationship',
			'get_relationships',
			'get_module_fields',
			'get_available_modules',
			'get_user_id',
		);
		$schema['properties']['method']['default']     = 'set_entry';

		return $schema;
	},
	10,
	2
);

/**
 * Set default template fields and configuration for SuiteCRM.
 */
add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'suitecrm' !== $addon ) {
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
						'value' => 'Basic',
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
					array(
						'ref'         => '#backend',
						'name'        => 'base_url',
						'label'       => __( 'SuiteCRM URL', 'forms-bridge' ),
						'description' => __(
							'Base URL of your SuiteCRM installation (e.g., https://crm.example.coop)',
							'forms-bridge'
						),
						'type'        => 'url',
						'required'    => true,
					),
					array(
						'ref'     => '#backend',
						'name'    => 'name',
						'default' => 'SuiteCRM',
					),
					array(
						'ref'   => '#backend/headers[]',
						'name'  => 'Content-Type',
						'value' => 'application/x-www-form-urlencoded',
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'endpoint',
						'label'    => __( 'Module', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'method',
						'label'    => __( 'Method', 'forms-bridge' ),
						'type'     => 'text',
						'value'    => 'set_entry',
						'required' => true,
					),
				),
				'bridge'     => array(
					'name'     => '',
					'form_id'  => '',
					'backend'  => '',
					'endpoint' => '',
					'method'   => 'set_entry',
				),
				'backend'    => array(
					'name'    => 'SuiteCRM',
					'headers' => array(
						array(
							'name'  => 'Content-Type',
							'value' => 'application/x-www-form-urlencoded',
						),
						array(
							'name'  => 'Accept',
							'value' => 'application/json',
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
