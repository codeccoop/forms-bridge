<?php
/**
 * Nextcloud addon hooks
 *
 * @package formsbridge
 */

use FORMS_BRIDGE\Nextcloud_Form_Bridge;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( 'nextcloud' !== $addon ) {
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
		if ( 'nextcloud' !== $addon ) {
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
						'ref'      => '#credential',
						'name'     => 'client_id',
						'label'    => __( 'User login', 'forms-bridge' ),
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

		if ( empty( $data['form']['fields'] ) ) {
			$credential_data         = $data['credential'];
			$credential_data['name'] = '__nextcloud-' . time();

			Credential::temp_registration( $credential_data );

			$backend_data               = $data['backend'];
			$backend_data['credential'] = $credential_data['name'];
			$backend_data['name']       = '__nextcloud-' . time();

			Backend::temp_registration( $backend_data );

			$bridge_data            = $data['bridge'];
			$bridge_data['name']    = '__nextcloud-' . time();
			$bridge_data['backend'] = $backend_data['name'];

			$bridge = new Nextcloud_Form_Bridge( $bridge_data );

			$headers = $bridge->table_headers();

			if ( is_array( $headers ) ) {
				foreach ( $headers as $header ) {
					$field_name = sanitize_title( $header );

					$data['form']['fields'][] = array(
						'name'  => $field_name,
						'label' => $header,
						'type'  => 'text',
					);

					if ( $header !== $field_name ) {
						if ( ! isset( $data['bridge']['mutations'][0] ) ) {
							$data['bridge']['mutations'][0] = array();
						}

						$data['bridge']['mutations'][0][] = array(
							'from' => $field_name,
							'to'   => $header,
							'cast' => 'inherit',
						);
					}
				}
			}
		}

		return $data;
	},
	10,
	2
);
