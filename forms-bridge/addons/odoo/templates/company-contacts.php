<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Company Contacts', 'forms-bridge' ),
	'description' => __(
		'Contact form for companies template. The resulting bridge will convert form submissions into new companies linked to contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Company Contacts', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'res.partner',
		),
	),
	'bridge'      => array(
		'endpoint'  => 'res.partner',
		'workflow'  => array(
			'iso2-country-code',
			'vat-id',
			'country-id',
			'contact-company',
			'skip-if-partner-exists',
		),
		'mutations' => array(
			array(
				array(
					'from' => 'company_name',
					'to'   => 'name',
					'cast' => 'string',
				),
			),
			array(),
			array(
				array(
					'from' => 'email',
					'to'   => 'contact_email',
					'cast' => 'copy',
				),
				array(
					'from' => 'phone',
					'to'   => 'contact_phone',
					'cast' => 'copy',
				),
			),
			array(),
			array(
				array(
					'from' => 'contact_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'contact_email',
					'to'   => 'email',
					'cast' => 'string',
				),
				array(
					'from' => 'contact_phone',
					'to'   => 'phone',
					'cast' => 'string',
				),
			),
		),
		array(),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'Company name', 'forms-bridge' ),
				'name'     => 'company_name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Tax ID', 'forms-bridge' ),
				'name'     => 'vat',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Address', 'forms-bridge' ),
				'name'     => 'street',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'City', 'forms-bridge' ),
				'name'     => 'city',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Zip code', 'forms-bridge' ),
				'name'     => 'zip',
				'type'     => 'text',
				'required' => true,
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
			array(
				'label'    => __( 'Your name', 'forms-bridge' ),
				'name'     => 'contact_name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Job position', 'forms-bridge' ),
				'name'     => 'function',
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
		),
	),
);
