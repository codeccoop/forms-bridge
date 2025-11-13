<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_country_phone_codes;

return array(
	'title'       => __( 'Companies', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will convert form submissions into companies linked to new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/v3/companies',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'owner',
			'label'       => __( 'Owner email', 'forms-bridge' ),
			'description' => __(
				'Email of the owner user of the company contact',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/v3/organization/invited/users',
				'finger'   => 'users[].email',
			),
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Companies', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Companies', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'company_name',
				'label'    => __( 'Company', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'country',
				'label'    => __( 'Country', 'forms-bridge' ),
				'type'     => 'select',
				'options'  => array_map(
					function ( $country ) {
						return array(
							'value' => $country,
							'label' => $country,
						);
					},
					array_values( $forms_bridge_country_phone_codes )
				),
				'required' => true,
			),
			array(
				'name'  => 'phone',
				'label' => __( 'Phone', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'website',
				'label' => __( 'Website', 'forms-bridge' ),
				'type'  => 'url',
			),
			array(
				'name'  => 'industry',
				'label' => __( 'Industry', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'     => 'email',
				'label'    => __( 'Your email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'     => 'fname',
				'label'    => __( 'Your first name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => false,
			),
			array(
				'name'  => 'lname',
				'label' => __( 'Your last name', 'forms-bridge' ),
				'type'  => 'text',
			),
		),
	),
	'backend'     => array(
		'base_url' => 'https://api.brevo.com',
		'headers'  => array(
			array(
				'name'  => 'Accept',
				'value' => 'application/json',
			),
		),
	),
	'bridge'      => array(
		'method'        => 'POST',
		'endpoint'      => '/v3/companies',
		'custom_fields' => array(
			array(
				'name'  => 'attributes.LANGUAGE',
				'value' => '$locale',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'fname',
					'to'   => 'attributes.FNAME',
					'cast' => 'string',
				),
				array(
					'from' => 'lname',
					'to'   => 'attributes.LNAME',
					'cast' => 'string',
				),
			),
			array(),
			array(
				array(
					'from' => 'country',
					'to'   => 'country',
					'cast' => 'null',
				),
				array(
					'from' => 'company_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'phone',
					'to'   => 'attributes.phone_number',
					'cast' => 'string',
				),
				array(
					'from' => 'website',
					'to'   => 'attributes.domain',
					'cast' => 'string',
				),
				array(
					'from' => 'industry',
					'to'   => 'attributes.industry',
					'cast' => 'string',
				),
				array(
					'from' => '?owner',
					'to'   => 'attributes.owner',
					'cast' => 'string',
				),
			),
		),
		'workflow'      => array( 'linked-contact', 'country-phone-code' ),
	),
);
