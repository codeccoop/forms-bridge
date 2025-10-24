<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Loan Requests', 'forms-bridge' ),
	'description' => __(
		'Loans form template. The resulting bridge will convert form submissions into loan requests.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/campaign/{campaign_id}/loan_request',
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Loan Requests', 'forms-bridge' ),
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/campaign/{campaign_id}/loan_request',
		'mutations'     => array(
			array(
				array(
					'from' => 'loan_amount',
					'to'   => 'loan_amount',
					'cast' => 'integer',
				),
			),
			array(),
			array(
				array(
					'from' => 'country',
					'to'   => 'country_code',
					'cast' => 'string',
				),
			),
		),
		'custom_fields' => array(
			array(
				'name'  => 'lang',
				'value' => '$locale',
			),
		),
		'workflow'      => array( 'iso2-country-code', 'vat-id' ),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'Loan amount', 'forms-bridge' ),
				'name'     => 'loan_amount',
				'type'     => 'number',
				'required' => true,
				'min'      => 0,
			),
			array(
				'label'    => __( 'First name', 'forms-bridge' ),
				'name'     => 'firstname',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Last name', 'forms-bridge' ),
				'name'     => 'lastname',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'ID number', 'forms-bridge' ),
				'name'     => 'vat',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Nationality', 'forms-bridge' ),
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
				'label'    => __( 'Email', 'forms-bridge' ),
				'name'     => 'email',
				'type'     => 'email',
				'required' => true,
			),
			array(
				'label'    => __( 'Phone', 'forms-bridge' ),
				'name'     => 'phone',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Address', 'forms-bridge' ),
				'name'     => 'address',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Zip code', 'forms-bridge' ),
				'name'     => 'zip_code',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'City', 'forms-bridge' ),
				'name'     => 'city',
				'type'     => 'text',
				'required' => true,
			),
		),
	),
);
