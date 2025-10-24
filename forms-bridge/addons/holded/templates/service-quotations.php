<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Service Quotations', 'forms-bridge' ),
	'description' => __(
		'Service quotations form template. The resulting bridge will convert form submissions into quotations linked to new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Service Quotations', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/invoicing/v1/documents/estimate',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'tags',
			'label'       => __( 'Tags', 'forms-bridge' ),
			'description' => __( 'Tags separated by commas', 'forms-bridge' ),
			'type'        => 'text',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'serviceId',
			'label'    => __( 'Service', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => '/api/invoicing/v1/services',
				'finger'   => array(
					'value' => '[].id',
					'label' => '[].name',
				),
			),
			'required' => true,
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/invoicing/v1/documents/estimate',
		'custom_fields' => array(
			array(
				'name'  => 'type',
				'value' => 'client',
			),
			array(
				'name'  => 'isperson',
				'value' => '1',
			),
			array(
				'name'  => 'defaults.language',
				'value' => '$locale',
			),
			array(
				'name'  => 'date',
				'value' => '$timestamp',
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
					'from' => '?tags',
					'to'   => 'quotation_tags',
					'cast' => 'inherit',
				),
				array(
					'from' => 'your-name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'code',
					'to'   => 'vatnumber',
					'cast' => 'copy',
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
				array(
					'from' => 'serviceId',
					'to'   => 'items[0].serviceId',
					'cast' => 'string',
				),
				array(
					'from' => 'quantity',
					'to'   => 'items[0].units',
					'cast' => 'integer',
				),
			),
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
			array(
				array(
					'from' => '?quotation_tags',
					'to'   => 'tags',
					'cast' => 'inherit',
				),
			),
		),
		'workflow'      => array( 'iso2-country-code', 'prefix-vatnumber', 'contact-id' ),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'Quantity', 'forms-bridge' ),
				'name'     => 'quantity',
				'type'     => 'number',
				'default'  => 1,
				'min'      => 0,
				'required' => true,
			),
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
