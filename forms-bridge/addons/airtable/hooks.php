<?php
/**
 * Airtable addon hooks
 *
 * @package formsbridge
 */

use FORMS_BRIDGE\Airtable_Form_Bridge;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

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
	'forms_bridge_template_data',
	function ( $data, $template_id ) {
		if ( strpos( $template_id, 'airtable-' ) !== 0 ) {
			return $data;
		}

		if ( empty( $data['form']['fields'] ) ) {
			$credential_data         = $data['credential'];
			$credential_data['name'] = '__airtable-' . time();

			Credential::temp_registration( $credential_data );

			$backend_data               = $data['backend'];
			$backend_data['credential'] = $credential_data['name'];
			$backend_data['name']       = '__airtable-' . time();

			Backend::temp_registration( $backend_data );

			$bridge_data            = $data['bridge'];
			$bridge_data['name']    = '__airtable-' . time();
			$bridge_data['backend'] = $backend_data['name'];

			$bridge = new Airtable_Form_Bridge( $bridge_data );

			$fields = $bridge->get_fields();
			if ( ! is_wp_error( $fields ) ) {
				foreach ( $fields as $field ) {
					if (
						in_array(
							$field['type'],
							array(
								'aiText',
								'formula',
								'autoNumber',
								'button',
								'count',
								'createdBy',
								'createdTime',
								'lastModifiedBy',
								'lastModifiedTime',
								'rollup',
								'externalSyncSource',
								'multipleAttachments',
								'multipleCollaborators',
								'multipleLookupValues',
								'multipleRecordLinks',
							),
							true,
						)
					) {
						continue;
					}

					$field_name = sanitize_title( $field['name'] );
					$form_field = array(
						'name'  => $field_name,
						'label' => $field['name'],
					);

					switch ( $field['type'] ) {
						case 'rating':
						case 'number':
							$form_field['type'] = 'number';
							break;
						case 'checkbox':
							$form_field['type'] = 'checkbox';
							break;
						case 'multipleSelects':
						case 'singleSelect':
							$form_field['type']    = 'select';
							$form_field['options'] = array_map(
								function ( $choice ) {
									return array(
										'value' => $choice['name'],
										'label' => $choice['name'],
									);
								},
								$field['options']['choices'],
							);

							$form_field['is_multi'] = 'multipleSelects' === $field['type'];
							break;
						case 'date':
							$form_field['type'] = 'date';
							break;
						case 'multilineText':
							$form_field['type'] = 'textarea';
							break;
						default:
							$form_field['type'] = 'text';
							break;
					}

					$data['form']['fields'][] = $form_field;

					if ( $field['name'] !== $form_field['name'] ) {
						if ( ! isset( $data['bridge']['mutations'][0] ) ) {
							$data['bridge']['mutations'][0] = array();
						}

						$data['bridge']['mutations'][0][] = array(
							'from' => $form_field['name'],
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
