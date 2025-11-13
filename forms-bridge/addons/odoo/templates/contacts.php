<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Contacts', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will convert form submissions into contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Contacts', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'res.partner',
		),
	),
	'bridge'      => array(
		'endpoint'      => 'res.partner',
		'custom_fields' => array(
			array(
				'name'  => 'is_company',
				'value' => '0',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'is_company',
					'to'   => 'is_company',
					'cast' => 'boolean',
				),
				array(
					'from' => 'your-name',
					'to'   => 'name',
					'cast' => 'string',
				),
			),
			array(),
		),
		'workflow'      => array(
			'iso2-country-code',
			'country-id',
			'skip-if-partner-exists',
		),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'Your name', 'forms-bridge' ),
				'name'     => 'your-name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Your email', 'forms-bridge' ),
				'name'     => 'email',
				'type'     => 'email',
				'required' => true,
			),
			array(
				'label' => __( 'Your phone', 'forms-bridge' ),
				'name'  => 'phone',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Address', 'forms-bridge' ),
				'name'  => 'street',
				'type'  => 'text',
			),
			array(
				'label' => __( 'City', 'forms-bridge' ),
				'name'  => 'city',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Zip code', 'forms-bridge' ),
				'name'  => 'zip',
				'type'  => 'text',
			),
			array(
				'label'    => __( 'Country', 'forms-bridge' ),
				'name'     => 'country',
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
		),
	),
);
