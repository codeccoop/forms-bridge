<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Subscription Requests', 'forms-bridge' ),
	'description' => __(
		'Subscription form template. The resulting bridge will convert form submissions into subscription requests.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Subscription Requests', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/campaign/{campaign_id}/subscription_request',
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/campaign/{campaign_id}/subscription_request',
		'custom_fields' => array(
			array(
				'name'  => 'lang',
				'value' => '$locale',
			),
			array(
				'name'  => 'type',
				'value' => 'increase',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'ordered_parts',
					'to'   => 'ordered_parts',
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
		'workflow'      => array( 'iso2-country-code', 'vat-id' ),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'Ordered parts', 'forms-bridge' ),
				'name'     => 'ordered_parts',
				'type'     => 'number',
				'required' => true,
				'min'      => 1,
			),
			array(
				'label'    => __( 'Remuneration type', 'forms-bridge' ),
				'name'     => 'remuneration_type',
				'type'     => 'select',
				'required' => true,
				'options'  => array(
					array(
						'value' => 'cash',
						'label' => __( 'Cash', 'forms-bridge' ),
					),
					array(
						'value' => 'wallet',
						'label' => __( 'Wallet', 'forms-bridge' ),
					),
				),
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
