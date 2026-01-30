<?php
/**
 * Grist addon hooks
 *
 * @package formsbridge
 */

use FORMS_BRIDGE\Grist_Form_Bridge;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'grist' !== $addon ) {
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
							'Register your Personal Access Token in your <a target="_blank" href="https://docs.getgrist.com/account">Grist account settings page</a>',
							'forms-bridge'
						),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'   => '#credential',
						'name'  => 'expires_at',
						'type'  => 'number',
						'value' => time() + 60 * 60 * 24 * 365 * 100,
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'endpoint',
						'label'    => __( 'Table', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
						'options'  => array(
							'endpoint' => '/api/orgs/{orgId}/tables',
							'finger'   => array(
								'value' => 'tables[].endpoint',
								'label' => 'tables[].label',
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
						'default' => 'Grist API',
					),
					array(
						'ref'     => '#backend',
						'name'    => 'base_url',
						'default' => 'https://docs.getgrist.com',
					),
					array(
						'ref'         => '#backend/headers[]',
						'name'        => 'orgId',
						'label'       => __( 'Team ID', 'forms-bridge' ),
						'description' => __(
							'Use `docs` by default for personal sites. If you\'ve created team site, it should be the team subdomain (e.g. `example` from https://example.getgrist.com). In self-hosted instances, the team ID is the last part of the team\'s homepage URL (e.g. `example` from http://localhost:8484/o/example)',
							'forms-bridge',
						),
						'type'        => 'text',
						'required'    => true,
						'default'     => 'docs',
					),
				),
				'backend'    => array(),
				'bridge'     => array(
					'backend'  => 'Grist API',
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
	'forms_bridge_template_data',
	function ( $data, $template_id ) {
		if ( 0 !== strpos( $template_id, 'grist-' ) ) {
			return $data;
		}

		if ( empty( $data['form']['fields'] ) ) {
			$credential_data         = $data['credential'];
			$credential_data['name'] = '__grist-' . time();

			Credential::temp_registration( $credential_data );

			$backend_data               = $data['backend'];
			$backend_data['credential'] = $credential_data['name'];
			$backend_data['name']       = '__grist-' . time();

			Backend::temp_registration( $backend_data );

			$bridge_data            = $data['bridge'];
			$bridge_data['name']    = '__grist-' . time();
			$bridge_data['backend'] = $backend_data['name'];

			$bridge = new Grist_Form_Bridge( $bridge_data );

			$fields = $bridge->get_fields();
			if ( ! is_wp_error( $fields ) ) {
				foreach ( $fields as $field ) {
					$field_name = $field['name'];
					$sanitized  = sanitize_title( $field_name );
					if ( strtolower( $field_name ) !== $sanitized ) {
						$field['name'] = $sanitized;
					}

					$data['form']['fields'][] = $field;

					if ( $field['name'] !== $field_name ) {
						if ( ! isset( $data['bridge']['mutations'][0] ) ) {
							$data['bridge']['mutations'][0] = array();
						}

						if ( 'file' === $field['type'] ) {
							$data['bridge']['mutations'][0][] = array(
								'from' => $field['name'] . '_filename',
								'to'   => $field['name'],
								'cast' => 'null',
							);
						}

						$data['bridge']['mutations'][0][] = array(
							'from' => $field['name'],
							'to'   => $field['name'],
							'cast' => 'inherit',
						);
					}
				}
			}
		}

		return $data;
	},
	10,
	2,
);
