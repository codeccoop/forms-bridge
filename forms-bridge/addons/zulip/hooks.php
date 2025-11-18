<?php
/**
 * Zulip addon hooks
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'zulip' !== $addon ) {
			return $defaults;
		}

		return wpct_plugin_merge_object(
			array(
				'fields'     => array(
					array(
						'ref'         => '#backend',
						'name'        => 'name',
						'description' => __(
							'Label of the Zulip API backend connection',
							'forms-bridge'
						),
						'default'     => 'Zulip API',
					),
					array(
						'ref'         => '#backend',
						'name'        => 'base_url',
						'description' => __(
							'Base URL of your Zulip',
							'forms-bridge'
						),
						'type'        => 'url',
						'default'     => 'https://your-organization.zulipchat.com',
					),
					array(
						'ref'   => '#backend/headers[]',
						'name'  => 'Content-Type',
						'value' => 'application/x-www-form-urlencoded',
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
						'ref'   => '#credential',
						'name'  => 'client_id',
						'label' => __( 'User email', 'forms-bridge' ),
						'type'  => 'text',
					),
					array(
						'ref'         => '#credential',
						'name'        => 'client_secret',
						'label'       => __( 'API key', 'forms-bridge' ),
						'description' => __(
							'You can get it from the "Account & privacy" section of your profile menu',
							'forms-bridge'
						),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
				),
				'bridge'     => array(
					'backend'  => '',
					'endpoint' => '',
					'method'   => 'POST',
				),
				'backend'    => array(
					'name'    => 'Zulip API',
					'headers' => array(
						array(
							'name'  => 'Content-Type',
							'value' => 'application/x-www-form-urlencoded',
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
