<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Leads', 'forms-bridge' ),
	'description' => __(
		'Lead form template. The resulting bridge will convert form submissions into leads linked to new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Leads', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/crm/v1/leads',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'funnelId',
			'label'    => __( 'Funnel', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => '/api/crm/v1/funnels',
				'finger'   => array(
					'value' => '[].id',
					'label' => '[].name',
				),
			),
			'required' => true,
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'value',
			'label'       => __( 'Lead value', 'forms-bridge' ),
			'description' => __(
				'Estimated deal value in currency units',
				'forms-bridge'
			),
			'type'        => 'number',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'potential',
			'description' => __(
				'Deal potential as a percentage',
				'forms-bridge'
			),
			'label'       => __( 'Lead potential (%)', 'forms-bridge' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 100,
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'tags',
			'label'       => __( 'Contact tags', 'forms-bridge' ),
			'description' => __( 'Tags separated by commas', 'forms-bridge' ),
			'type'        => 'text',
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/crm/v1/leads',
		'custom_fields' => array(
			array(
				'name'  => 'isperson',
				'value' => '1',
			),
			array(
				'name'  => 'type',
				'value' => 'lead',
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
				array(
					'from' => '?tags',
					'to'   => 'lead_tags',
					'cast' => 'inherit',
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
					'from' => '?lead_tags',
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
