<?php
/**
 * Mautic addon hooks.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'mautic' !== $addon ) {
			return $defaults;
		}

		return wpct_plugin_merge_object(
			array(
				'fields'     => array(
					array(
						'ref'         => '#backend',
						'name'        => 'name',
						'description' => __(
							'Label of the Mautic API backend connection',
							'forms-bridge'
						),
						'default'     => 'Mautic API',
					),
					array(
						'ref'         => '#backend',
						'name'        => 'base_url',
						'description' => __(
							'Base URL of your Mautic instance',
							'forms-bridge'
						),
						'type'        => 'url',
						'default'     => 'https://mautic.example.coop',
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
						'label'       => __( 'User', 'forms-bridge' ),
						'description' => __(
							'Username of a Mautic user with API permissions. The API and the HTTP basic auth have to be enabled on the API Settings section of your Mautic configuration',
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
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
				),
				'bridge'     => array(
					'method' => 'POST',
				),
				'backend'    => array(
					'name'    => 'Mautic API',
					'headers' => array(
						array(
							'name'  => 'Content-Type',
							'value' => 'application/json',
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

add_filter(
	'forms_bridge_template_data',
	function ( $data, $template_id ) {
		if ( strpos( $template_id, 'mautic-' ) !== 0 ) {
			return $data;
		}

		$index = array_search(
			'segments',
			array_column( $data['bridge']['custom_fields'], 'name' ),
			true,
		);

		if ( false !== $index ) {
			$field = $data['bridge']['custom_fields'][ $index ];
			$value = is_array( $field['value'] ) ? $field['value'] : array( $field['value'] );

			array_splice( $data['bridge']['custom_fields'], $index, 1 );

			$i = 0;
			foreach ( $value as $segment_id ) {
				if ( '' === $segment_id || null === $segment_id ) {
					continue;
				}

				$data['bridge']['custom_fields'][] = array(
					'name'  => "segments[{$i}]",
					'value' => (int) $segment_id,
				);

				$data['bridge']['mutations'][0][] = array(
					'from' => "segments[{$i}]",
					'to'   => "segments[{$i}]",
					'cast' => 'integer',
				);

				++$i;
			}

			$data['bridge']['custom_fields'] = array_values(
				$data['bridge']['custom_fields']
			);
		}

		return $data;
	},
	10,
	2
);
