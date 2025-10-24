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
			'value' => '/api/invoicing/v1/contacts',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'type',
			'label'    => __( 'Contact type', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				array(
					'label' => __( 'Unspecified', 'forms-bridge' ),
					'value' => '0',
				),
				array(
					'label' => __( 'Client', 'forms-bridge' ),
					'value' => 'client',
				),
				array(
					'label' => __( 'Lead', 'forms-bridge' ),
					'value' => 'lead',
				),
				array(
					'label' => __( 'Supplier', 'forms-bridge' ),
					'value' => 'supplier',
				),
				array(
					'label' => __( 'Debtor', 'forms-bridge' ),
					'value' => 'debtor',
				),
				array(
					'label' => __( 'Creditor', 'forms-bridge' ),
					'value' => 'creditor',
				),
			),
			'required' => true,
			'default'  => '0',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'tags',
			'label'       => __( 'Tags', 'forms-bridge' ),
			'description' => __( 'Tags separated by commas', 'forms-bridge' ),
			'type'        => 'text',
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/invoicing/v1/contacts',
		'custom_fields' => array(
			array(
				'name'  => 'isperson',
				'value' => '1',
			),
			array(
				'name'  => 'defaults.language',
				'value' => '$locale',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'isperson',
					'to'   => 'isperson',
					'cast' => 'integer',
				),
				array(
					'from' => 'code',
					'to'   => 'vatnumber',
					'cast' => 'copy',
				),
				array(
					'from' => 'your-name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'address',
					'to'   => 'billAddress.address',
					'cast' => 'string',
				),
				array(
					'from' => 'postalCode',
					'to'   => 'billAddress.postalCode',
					'cast' => 'string',
				),
				array(
					'from' => 'city',
					'to'   => 'billAddress.city',
					'cast' => 'string',
				),
			),
			array(),
			array(
				array(
					'from' => 'country',
					'to'   => 'countryCode',
					'cast' => 'string',
				),
			),
			array(
				array(
					'from' => 'countryCode',
					'to'   => 'billAddress.countryCode',
					'cast' => 'string',
				),
			),
		),
		'workflow'      => array(
			'skip-if-contact-exists',
			'iso2-country-code',
			'prefix-vatnumber',
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
				'label'    => __( 'Tax ID', 'forms-bridge' ),
				'name'     => 'code',
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
				'label'    => __( 'Your phone', 'forms-bridge' ),
				'name'     => 'phone',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label' => __( 'Address', 'forms-bridge' ),
				'name'  => 'address',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Zip code', 'forms-bridge' ),
				'name'  => 'postalCode',
				'type'  => 'text',
			),
			array(
				'label' => __( 'City', 'forms-bridge' ),
				'name'  => 'city',
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
