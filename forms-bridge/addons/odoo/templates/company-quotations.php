<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Company Quotations', 'forms-bridge' ),
	'description' => __(
		'Quotations form template. The resulting bridge will convert form submissions into quotations linked to new companies.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Company Quotations', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'sale.order',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'product_id',
			'label'    => __( 'Product', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => 'product.product',
				'finger'   => array(
					'value' => 'result[].id',
					'label' => 'result[].name',
				),
			),
			'required' => true,
		),
	),
	'bridge'      => array(
		'endpoint'      => 'sale.order',
		'custom_fields' => array(
			array(
				'name'  => 'state',
				'value' => 'draft',
			),
			array(
				'name'  => 'order_line[0][0]',
				'value' => '0',
			),
			array(
				'name'  => 'order_line[0][1]',
				'value' => '0',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'order_line[0][0]',
					'to'   => 'order_line[0][0]',
					'cast' => 'integer',
				),
				array(
					'from' => 'order_line[0][1]',
					'to'   => 'order_line[0][1]',
					'cast' => 'integer',
				),
				array(
					'from' => 'quantity',
					'to'   => 'order_line[0][2].product_uom_qty',
					'cast' => 'integer',
				),
				array(
					'from' => 'product_id',
					'to'   => 'order_line[0][2].product_id',
					'cast' => 'integer',
				),
				array(
					'from' => 'company-name',
					'to'   => 'name',
					'cast' => 'string',
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
					'from' => 'your-name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'parent_id',
					'to'   => 'company_partner_id',
					'cast' => 'copy',
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
					'from' => 'company_partner_id',
					'to'   => 'partner_id',
					'cast' => 'integer',
				),
			),
		),
		'workflow'      => array(
			'iso2-country-code',
			'vat-id',
			'country-id',
			'contact-company',
			'contact',
		),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'Quantity', 'forms-bridge' ),
				'name'     => 'quantity',
				'type'     => 'number',
				'required' => true,
				'default'  => 1,
				'min'      => 1,
			),
			array(
				'label'    => __( 'Company', 'forms-bridge' ),
				'name'     => 'company-name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Vat ID', 'forms-bridge' ),
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
				'name'     => 'your-name',
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
