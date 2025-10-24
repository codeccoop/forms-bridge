<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Thirdparties', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will convert form submissions into thirdparties.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/index.php/thirdparties',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'client',
			'label'    => __( 'Thirdparty status', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				array(
					'value' => '1',
					'label' => __( 'Client', 'forms-bridge' ),
				),
				array(
					'value' => '2',
					'label' => __( 'Prospect', 'forms-bridge' ),
				),
				array(
					'value' => '3',
					'label' => __( 'Client/Prospect', 'forms-bridge' ),
				),
				array(
					'value' => '0',
					'label' => __(
						'Neither customer nor supplier',
						'forms-bridge'
					),
				),
			),
			'required' => true,
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'typent_id',
			'label'   => __( 'Thirdparty type', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				array(
					'label' => __( 'Large company', 'forms-bridge' ),
					'value' => '2',
				),
				array(
					'label' => __( 'Medium company', 'forms-bridge' ),
					'value' => '3',
				),
				array(
					'label' => __( 'Small company', 'forms-bridge' ),
					'value' => '4',
				),
				array(
					'label' => __( 'Governmental', 'forms-bridge' ),
					'value' => '5',
				),
				array(
					'label' => __( 'Startup', 'forms-bridge' ),
					'value' => '1',
				),
				array(
					'label' => __( 'Retailer', 'forms-bridge' ),
					'value' => '7',
				),
				array(
					'label' => __( 'Private individual', 'forms-bridge' ),
					'value' => '8',
				),
				array(
					'label' => __( 'Other', 'forms-bridge' ),
					'value' => '100',
				),
			),
		),
		// [
		// 'ref' => '#bridge/custom_fields[]',
		// 'name' => 'fournisseur',
		// 'label' => __('Provider', 'forms-bridge'),
		// 'type' => 'boolean',
		// 'default' => false,
		// ],
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Thirdparties', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Thirdparties', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'name',
				'label'    => __( 'Name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'tva_intra',
				'label'    => __( 'Tax ID', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'email',
				'label'    => __( 'Email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'     => 'phone',
				'label'    => __( 'Phone', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'address',
				'label'    => __( 'Address', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'zip',
				'label'    => __( 'Postal code', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'town',
				'label'    => __( 'City', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'country',
				'label'    => __( 'Country', 'forms-bridge' ),
				'type'     => 'select',
				'options'  => array_map(
					function ( $country_code ) {
						global $forms_bridge_iso2_countries;
						return array(
							'value' => $country_code,
							'label' => $forms_bridge_iso2_countries[ $country_code ],
						);
					},
					array_keys( $forms_bridge_iso2_countries )
				),
				'required' => true,
			),
			array(
				'name'  => 'note_private',
				'label' => __( 'Comments', 'forms-bridge' ),
				'type'  => 'textarea',
			),
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/index.php/thirdparties',
		'custom_fields' => array(
			array(
				'name'  => 'status',
				'value' => '1',
			),
		),
		'workflow'      => array(
			'iso2-country-code',
			'country-id',
			'skip-if-thirdparty-exists',
			'next-client-code',
		),
	),
);
