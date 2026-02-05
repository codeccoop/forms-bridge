<?php
/**
 * Holded addon hooks.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( 'holded' !== $addon ) {
			return $schema;
		}

		unset( $schema['properties']['credential'] );
		return $schema;
	},
	10,
	2
);

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'holded' !== $addon ) {
			return $defaults;
		}

		return wpct_plugin_merge_object(
			array(
				'fields'  => array(
					array(
						'ref'         => '#backend',
						'name'        => 'name',
						'description' => __(
							'Label of the Holded API backend connection',
							'forms-bridge'
						),
						'default'     => 'Holded API',
					),
					array(
						'ref'   => '#backend',
						'name'  => 'base_url',
						'value' => 'https://api.holded.com',
					),
					array(
						'ref'         => '#backend/headers[]',
						'name'        => 'key',
						'label'       => __( 'API Key', 'forms-bridge' ),
						'description' => __(
							'Get it from your <a href="https://app.holded.com/api" target="_blank">account</a>',
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
				'bridge'  => array(
					'method' => 'POST',
				),
				'backend' => array(
					'base_url' => 'https://api.holded.com',
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
		if ( 0 !== strpos( $template_id, 'holded-' ) ) {
			return $data;
		}

		$index = array_search(
			'tags',
			array_column( $data['bridge']['custom_fields'], 'name' ),
			true,
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
						'name'  => "tags[{$i}]",
						'value' => $tags[ $i ],
					);
				}
			}

			array_splice( $data['bridge']['custom_fields'], $index, 1 );
		}

		return $data;
	},
	10,
	2
);
