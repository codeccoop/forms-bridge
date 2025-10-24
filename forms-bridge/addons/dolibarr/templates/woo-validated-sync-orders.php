<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'        => __( 'Validated Orders + Sync', 'forms-bridge' ),
	'description'  => __(
		'Product sale order bridge template. The resulting bridge will convert WooCommerce orders into validated sale orders linked to new third parties. <b>The template includes a job that synchronize products between WooCommerce and Dolibarr by product refs.</b>.',
		'forms-bridge'
	),
	'integrations' => array( 'woo' ),
	'fields'       => array(
		array(
			'ref'   => '#form',
			'name'  => 'title',
			'value' => __( 'Woo Checkout', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/index.php/orders',
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
					'label' => __( 'Retailer', 'forms-bridge' ),
					'value' => '7',
				),
				array(
					'label' => __( 'Private individual', 'forms-bridge' ),
					'value' => '8',
				),
				array(
					'label' => __( 'Other', 'forms-bridge' ),
					'value' => '100',
				),
			),
		),
	),
	'bridge'       => array(
		'endpoint'      => '/api/index.php/orders',
		'custom_fields' => array(
			array(
				'name'  => 'status',
				'value' => '1',
			),
			array(
				'name'  => 'client',
				'value' => '1',
			),
			array(
				'name'  => 'date',
				'value' => '$timestamp',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'id',
					'to'   => 'id',
					'cast' => 'null',
				),
				array(
					'from' => 'parent_id',
					'to'   => 'parent_id',
					'cast' => 'null',
				),
				array(
					'from' => 'version',
					'to'   => 'version',
					'cast' => 'null',
				),
				array(
					'from' => 'currency',
					'to'   => 'currency',
					'cast' => 'null',
				),
				array(
					'from' => 'prices_include_tax',
					'to'   => 'prices_include_tax',
					'cast' => 'null',
				),
				array(
					'from' => 'date_created',
					'to'   => 'date_created',
					'cast' => 'null',
				),
				array(
					'from' => 'date_modified',
					'to'   => 'date',
					'cast' => 'null',
				),
				array(
					'from' => 'discount_total',
					'to'   => 'discount_total',
					'cast' => 'null',
				),
				array(
					'from' => 'discount_tax',
					'to'   => 'discount_tax',
					'cast' => 'null',
				),
				array(
					'from' => 'shipping_total',
					'to'   => 'shipping_total',
					'cast' => 'null',
				),
				array(
					'from' => 'shipping_tax',
					'to'   => 'shipping_tax',
					'cast' => 'null',
				),
				array(
					'from' => 'cart_total',
					'to'   => 'cart_total',
					'cast' => 'null',
				),
				array(
					'from' => 'cart_tax',
					'to'   => 'cart_tax',
					'cast' => 'null',
				),
				array(
					'from' => 'total',
					'to'   => 'total',
					'cast' => 'null',
				),
				array(
					'from' => 'total_tax',
					'to'   => 'total_tax',
					'cast' => 'null',
				),
				array(
					'from' => 'customer_id',
					'to'   => 'customer_id',
					'cast' => 'null',
				),
				array(
					'from' => 'order_key',
					'to'   => 'order_key',
					'cast' => 'null',
				),
				array(
					'from' => 'billing.first_name',
					'to'   => 'name[0]',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.last_name',
					'to'   => 'name[1]',
					'cast' => 'string',
				),
				array(
					'from' => 'name',
					'to'   => 'name',
					'cast' => 'concat',
				),
				array(
					'from' => '?billing.address_1',
					'to'   => 'address',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.city',
					'to'   => 'town',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.postcode',
					'to'   => 'zip',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.email',
					'to'   => 'email',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.email',
					'to'   => 'shipping.email',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.phone',
					'to'   => 'phone',
					'cast' => 'string',
				),
				array(
					'from' => 'billing',
					'to'   => 'billing',
					'cast' => 'null',
				),
				array(
					'from' => 'payment_method',
					'to'   => 'payment_method',
					'cast' => 'null',
				),
				array(
					'from' => 'payment_method_title',
					'to'   => 'payment_method_title',
					'cast' => 'null',
				),
				array(
					'from' => 'transaction_id',
					'to'   => 'transaction_id',
					'cast' => 'null',
				),
				array(
					'from' => 'customer_ip_address',
					'to'   => 'customer_ip_address',
					'cast' => 'null',
				),
				array(
					'from' => 'customer_user_agent',
					'to'   => 'customer_user_agent',
					'cast' => 'null',
				),
				array(
					'from' => 'created_via',
					'to'   => 'created_via',
					'cast' => 'null',
				),
				array(
					'from' => '?customer_note',
					'to'   => 'customer_note',
					'cast' => 'string',
				),
				array(
					'from' => 'date_completed',
					'to'   => 'date_completed',
					'cast' => 'null',
				),
				array(
					'from' => 'date_paid',
					'to'   => 'date_paid',
					'cast' => 'null',
				),
				array(
					'from' => 'cart_hash',
					'to'   => 'cart_hash',
					'cast' => 'null',
				),
				array(
					'from' => 'order_stock_reduced',
					'to'   => 'order_stock_reduced',
					'cast' => 'null',
				),
				array(
					'from' => 'download_permissions_granted',
					'to'   => 'download_permissions_granted',
					'cast' => 'null',
				),
				array(
					'from' => 'new_order_email_sent',
					'to'   => 'new_order_email_sent',
					'cast' => 'null',
				),
				array(
					'from' => 'recorded_sales',
					'to'   => 'recorded_sales',
					'cast' => 'null',
				),
				array(
					'from' => 'recorded_coupon_usage_counts',
					'to'   => 'recorded_coupon_usage_counts',
					'cast' => 'null',
				),
				array(
					'from' => 'number',
					'to'   => 'number',
					'cast' => 'null',
				),
				array(
					'from' => 'tax_lines',
					'to'   => 'tax_lines',
					'cast' => 'null',
				),
				array(
					'from' => 'shipping_lines',
					'to'   => 'shipping_lines',
					'cast' => 'null',
				),
				array(
					'from' => 'fee_lines',
					'to'   => 'fee_lines',
					'cast' => 'null',
				),
				array(
					'from' => 'coupon_lines',
					'to'   => 'coupon_lines',
					'cast' => 'null',
				),
				array(
					'from' => 'line_items[].quantity',
					'to'   => 'lines[].qty',
					'cast' => 'copy',
				),
				array(
					'from' => 'line_items[].subtotal_tax.percentage',
					'to'   => 'lines[].tva_tx',
					'cast' => 'copy',
				),
				array(
					'from' => 'line_items[].product.price',
					'to'   => 'lines[].subprice',
					'cast' => 'copy',
				),
				array(
					'from' => 'line_items[].product.sku',
					'to'   => 'lines[].ref',
					'cast' => 'copy',
				),
			),
			array(
				array(
					'from' => 'line_items',
					'to'   => 'line_items',
					'cast' => 'null',
				),
			),
			array(
				array(
					'from' => 'socid',
					'to'   => 'order_socid',
					'cast' => 'copy',
				),
				array(
					'from' => '?shipping.first_name',
					'to'   => 'firstname',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.last_name',
					'to'   => 'lastname',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.email',
					'to'   => 'email',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.phone',
					'to'   => 'phone_perso',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.address_1',
					'to'   => 'address',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.postcode',
					'to'   => 'zip',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.city',
					'to'   => 'town',
					'cast' => 'string',
				),
				array(
					'from' => 'shipping',
					'to'   => 'shipping',
					'cast' => 'null',
				),
			),
			array(
				array(
					'from' => 'lines[].ref',
					'to'   => 'product_refs',
					'cast' => 'inherit',
				),
				array(
					'from' => 'contact_ids',
					'to'   => 'contact_ids',
					'cast' => 'null',
				),
			),
			array(
				array(
					'from' => '?customer_note',
					'to'   => 'note_private',
					'cast' => 'string',
				),
				array(
					'from' => 'order_socid',
					'to'   => 'socid',
					'cast' => 'integer',
				),
				array(
					'from' => 'fk_products[]',
					'to'   => 'lines[].fk_product',
					'cast' => 'integer',
				),
			),
		),
		'workflow'      => array(
			'sync-products-by-ref',
			'contact-socid',
			'contact-id',
			'products-by-ref',
			'validate-order',
		),
	),
);
