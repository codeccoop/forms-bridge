<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'        => __( 'WC Orders', 'forms-bridge' ),
	'description'  => __(
		'Sale order bridge template. The resulting bridge will convert WooCommerce orders into new lines on a Google Spreadsheet with order and customer information.',
		'forms-bridge'
	),
	'integrations' => array( 'woo' ),
	'fields'       => array(
		array(
			'ref'   => '#form',
			'name'  => 'title',
			'value' => __( 'Woo Checkout', 'forms-bridge' ),
		),
	),
	'bridge'       => array(
		'mutations' => array(
			array(
				array(
					'from' => 'id',
					'to'   => 'Order ID',
					'cast' => 'integer',
				),
				array(
					'from' => 'line_items[].product.name',
					'to'   => 'Name[][0]',
					'cast' => 'string',
				),
				array(
					'from' => 'line_items[].quantity',
					'to'   => 'Name[][1]',
					'cast' => 'string',
				),
				array(
					'from' => 'Name[]',
					'to'   => 'Name[]',
					'cast' => 'concat',
				),
				array(
					'from' => 'Name',
					'to'   => 'Name',
					'cast' => 'csv',
				),
				array(
					'from' => 'line_items',
					'to'   => 'line_items',
					'cast' => 'null',
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
					'from' => 'currency',
					'to'   => 'currency',
					'cast' => 'null',
				),
				array(
					'from' => 'tax_lines',
					'to'   => 'tax_lines',
					'cast' => 'null',
				),
				array(
					'from' => 'fee_lines',
					'to'   => 'fee_lines',
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
					'to'   => 'Date',
					'cast' => 'string',
				),
				array(
					'from' => 'total',
					'to'   => 'Total',
					'cast' => 'number',
				),
				array(
					'from' => 'total_tax',
					'to'   => 'total_tax',
					'cast' => 'null',
				),
				array(
					'from' => 'shipping_lines[].name',
					'to'   => 'Shipping',
					'cast' => 'string',
				),
				array(
					'from' => 'Shipping',
					'to'   => 'Shipping',
					'cast' => 'csv',
				),
				array(
					'from' => 'shipping_lines',
					'to'   => 'shipping_lines',
					'cast' => 'null',
				),
				array(
					'from' => 'shipping_total',
					'to'   => 'Shipping Total',
					'cast' => 'number',
				),
				array(
					'from' => 'shipping_tax',
					'to'   => 'shipping_tax',
					'cast' => 'null',
				),
				array(
					'from' => 'coupon_lines[].code',
					'to'   => 'Coupons',
					'cast' => 'string',
				),
				array(
					'from' => 'Coupons',
					'to'   => 'Coupons',
					'cast' => 'csv',
				),
				array(
					'from' => 'coupon_lines',
					'to'   => 'coupon_lines',
					'cast' => 'null',
				),
				array(
					'from' => 'discount_total',
					'to'   => 'Discount Total',
					'cast' => 'number',
				),
				array(
					'from' => 'discount_tax',
					'to'   => 'discount_tax',
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
					'to'   => 'First Name',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.last_name',
					'to'   => 'Last Name',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.email',
					'to'   => 'Email',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.phone',
					'to'   => 'Phone',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.address_1',
					'to'   => 'Address',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.address_2',
					'to'   => 'Address 2',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.city',
					'to'   => 'City',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.postcode',
					'to'   => 'Postal Code',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.state',
					'to'   => 'State',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.country',
					'to'   => 'Country',
					'cast' => 'string',
				),
				array(
					'from' => 'billing',
					'to'   => 'billing',
					'cast' => 'null',
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
					'to'   => 'Payment Method',
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
					'to'   => 'Note',
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
			),
		),
	),
);
