<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Proposals', 'forms-bridge' ),
	'description' => __(
		'Quotations form template. The resulting bridge will convert form submissions into quotations linked to new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/index.php/proposals',
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
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'fk_product',
			'label'    => __( 'Product', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => '/api/index.php/products',
				'finger'   => array(
					'value' => '[].id',
					'label' => '[].label',
				),
			),
			'required' => true,
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Proposals', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Proposals', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'quantity',
				'label'    => __( 'Quantity', 'forms-bridge' ),
				'type'     => 'number',
				'required' => true,
				'default'  => 1,
				'min'      => 1,
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
				'name'     => 'tva_intra',
				'label'    => __( 'Tax ID', 'forms-bridge' ),
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
				'name'  => 'phone',
				'label' => __( 'Phone', 'forms-bridge' ),
				'type'  => 'text',
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
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/index.php/proposals',
		'custom_fields' => array(
			array(
				'name'  => 'status',
				'value' => '1',
			),
			array(
				'name'  => 'typent_id',
				'value' => '8',
			),
			array(
				'name'  => 'client',
				'value' => '2',
			),
			array(
				'name'  => 'date',
				'value' => '$timestamp',
			),
			array(
				'name'  => 'lines[0].product_type',
				'value' => '1',
			),
		),
		'mutations'     => array(
			array(),
			array(
				array(
					'from' => 'firstname',
					'to'   => 'name[0]',
					'cast' => 'string',
				),
				array(
					'from' => 'lastname',
					'to'   => 'name[1]',
					'cast' => 'string',
				),
				array(
					'from' => 'name',
					'to'   => 'name',
					'cast' => 'concat',
				),
				array(
					'from' => 'quantity',
					'to'   => 'lines[0].qty',
					'cast' => 'integer',
				),
				array(
					'from' => 'fk_product',
					'to'   => 'lines[0].fk_product',
					'cast' => 'integer',
				),
			),
		),
		'workflow'      => array( 'iso2-country-code', 'country-id', 'contact-socid' ),
	),
);
