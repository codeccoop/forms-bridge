<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'CRM Company Leads', 'forms-bridge' ),
	'description' => __(
		'Leads form template. The resulting bridge will convert form submissions into leads linked to new companies.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'CRM Company Leads', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'crm.lead',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'user_id',
			'label'       => __( 'Owner email', 'forms-bridge' ),
			'description' => __(
				'Email of the owner user of the lead',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => 'res.users',
				'finger'   => array(
					'value' => 'result[].id',
					'label' => 'result[].name',
				),
			),
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'lead_name',
			'label'    => __( 'Lead name', 'forms-bridge' ),
			'type'     => 'text',
			'required' => true,
			'default'  => __( 'Web Lead', 'forms-bridge' ),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'priority',
			'label'   => __( 'Priority', 'forms-bridge' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 3,
			'default' => 1,
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'expected_revenue',
			'label'   => __( 'Expected revenue', 'forms-bridge' ),
			'type'    => 'number',
			'min'     => 0,
			'default' => 0,
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'tag_ids',
			'label'    => __( 'Lead tags', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => 'crm.tag',
				'finger'   => array(
					'value' => 'result[].id',
					'label' => 'result[].name',
				),
			),
			'is_multi' => true,
		),
	),
	'bridge'      => array(
		'endpoint'  => 'crm.lead',
		'mutations' => array(
			array(
				array(
					'from' => '?user_id',
					'to'   => 'user_id',
					'cast' => 'integer',
				),
				array(
					'from' => 'company_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => '?priority',
					'to'   => 'priority',
					'cast' => 'string',
				),
				array(
					'from' => '?expected_revenue',
					'to'   => 'expected_revenue',
					'cast' => 'number',
				),
			),
			array(),
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
			array(
				array(
					'from' => 'lead_name',
					'to'   => 'name',
					'cast' => 'string',
				),
			),
		),
		'workflow'  => array(
			'iso2-country-code',
			'vat-id',
			'country-id',
			'contact-company',
			'crm-contact',
		),
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
			array(
				'label'    => __( 'Comments', 'forms-bridge' ),
				'name'     => 'description',
				'type'     => 'textarea',
				'required' => true,
			),
		),
	),
);
