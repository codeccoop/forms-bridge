<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'        => __( 'Product Orders + Sync', 'forms-bridge' ),
	'description'  => __(
		'Product sale order bridge template. The resulting bridge will convert WooCommerce orders into product sale orders linked to new contacts. <b>The template includes a job that synchronize products between WooCommerce and Holded by product SKUs.</b>',
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
			'value' => '/api/invoicing/v1/documents/salesorder',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'tags',
			'label'       => __( 'Tags', 'forms-bridge' ),
			'description' => __( 'Tags separated by commas', 'forms-bridge' ),
			'type'        => 'text',
		),
	),
	'bridge'       => array(
		'endpoint'      => '/api/invoicing/v1/documents/salesorder',
		'custom_fields' => array(
			array(
				'name'  => 'type',
				'value' => 'client',
			),
			array(
				'name'  => 'defaults.language',
				'value' => '$locale',
			),
			array(
				'name'  => 'approveDoc',
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
					'from' => 'approveDoc',
					'to'   => 'approveDoc',
					'cast' => 'boolean',
				),
				array(
					'from' => 'id',
					'to'   => 'customFields.wp_order_id',
					'cast' => 'integer',
				),
				array(
					'from' => 'parent_id',
					'to'   => 'parent_id',
					'cast' => 'null',
				),
				array(
					'from' => 'status',
					'to'   => 'status',
					'cast' => 'null',
				),
				array(
					'from' => 'version',
					'to'   => 'version',
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
					'to'   => 'date_modified',
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
					'to'   => 'CustomId',
					'cast' => 'string',
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
					'to'   => 'billAddress.address',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.city',
					'to'   => 'billAddress.city',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.postcode',
					'to'   => 'billAddress.postalCode',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.state',
					'to'   => 'billAddress.province',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.country',
					'to'   => 'billAddress.country',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.email',
					'to'   => 'email',
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
					'from' => '?shipping.address_1',
					'to'   => 'shippingAddresses[0].address',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.postcode',
					'to'   => 'shippingAddresses[0].postalCode',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.city',
					'to'   => 'shippingAddresses[0].city',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.state',
					'to'   => 'shippingAddresses[0].province',
					'cast' => 'string',
				),
				array(
					'from' => 'shipping.country',
					'to'   => 'shippingAddresses[0].country',
					'cast' => 'string',
				),
				array(
					'from' => 'shipping',
					'to'   => 'shipping',
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
					'to'   => 'notes',
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
					'from' => 'line_items[].name',
					'to'   => 'items[].name',
					'cast' => 'copy',
				),
				array(
					'from' => 'line_items[].product.price',
					'to'   => 'items[].subtotal',
					'cast' => 'copy',
				),
				array(
					'from' => 'line_items[].total_tax.percentage',
					'to'   => 'items[].tax',
					'cast' => 'copy',
				),
				array(
					'from' => 'line_items[].quantity',
					'to'   => 'items[].units',
					'cast' => 'copy',
				),
				array(
					'from' => 'line_items[].product.sku',
					'to'   => 'items[].sku',
					'cast' => 'copy',
				),
				array(
					'from' => '?tags',
					'to'   => 'order_tags',
					'cast' => 'inherit',
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
					'from' => '?order_tags',
					'to'   => 'tags',
					'cast' => 'inherit',
				),
			),
		),
		'workflow'      => array( 'sync-products-by-sku', 'contact-id' ),
	),
);
