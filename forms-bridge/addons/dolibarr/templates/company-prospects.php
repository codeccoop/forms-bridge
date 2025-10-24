<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Company Prospects', 'forms-bridge' ),
	'description' => __(
		'Leads form template. The resulting bridge will convert form submissions into company prospects linked to new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/index.php/contacts',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'stcomm_id',
			'label'    => __( 'Prospect status', 'forms-bridge' ),
			'required' => true,
			'type'     => 'select',
			'options'  => array(
				array(
					'label' => __( 'Never contacted', 'forms-bridge' ),
					'value' => '0',
				),
				array(
					'label' => __( 'To contact', 'forms-bridge' ),
					'value' => '1',
				),
				array(
					'label' => __( 'Contact in progress', 'forms-bridge' ),
					'value' => '2',
				),
				array(
					'label' => __( 'Contacted', 'forms-bridge' ),
					'value' => '3',
				),
				array(
					'label' => __( 'Do not contact', 'forms-bridge' ),
					'value' => '-1',
				),
			),
			'default'  => '0',
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
					'label' => __( 'Other', 'forms-bridge' ),
					'value' => '100',
				),
			),
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Company Prospects', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Company Prospects', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'company_name',
				'label'    => __( 'Company name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'idprof1',
				'label'    => __( 'Tax ID', 'forms-bridge' ),
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
				'name'     => 'firstname',
				'label'    => __( 'First name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'lastname',
				'label'    => __( 'Last name', 'forms-bridge' ),
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
				'name'     => 'poste',
				'label'    => __( 'Job position', 'forms-bridge' ),
				'type'     => 'text',
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
		'endpoint'      => '/api/index.php/contacts',
		'custom_fields' => array(
			array(
				'name'  => 'status',
				'value' => '1',
			),
			array(
				'name'  => 'client',
				'value' => '2',
			),
		),
		'mutations'     => array(
			array(),
			array(),
			array(
				array(
					'from' => 'company_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'email',
					'to'   => 'contact_email',
					'cast' => 'copy',
				),
			),
			array(
				array(
					'from' => 'contact_email',
					'to'   => 'email',
					'cast' => 'string',
				),
			),
		),
		'workflow'      => array(
			'iso2-country-code',
			'country-id',
			'contact-socid',
			'skip-if-contact-exists',
		),
	),
);
