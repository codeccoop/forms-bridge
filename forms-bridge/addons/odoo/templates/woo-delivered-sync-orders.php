<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'        => __( 'Delivered Orders + Sync', 'forms-bridge' ),
	'description'  => __(
		'Sale order bridge template. The resulting bridge will convert WooCommerce orders into product delivered sale orders linked to new contacts. <b>The template includes a job that synchronize products between WooCommerce and Odoo by product refs.</b>',
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
			'value' => 'sale.order',
		),
	),
	'bridge'       => array(
		'endpoint'      => 'sale.order',
		'custom_fields' => array(
			array(
				'name'  => 'state',
				'value' => 'sale',
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
					'to'   => 'street',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.address_2',
					'to'   => 'street2',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.city',
					'to'   => 'city',
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
					'to'   => 'shipping.comment',
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
					'from' => 'line_items[].product.name',
					'to'   => 'order_line[][0]',
					'cast' => 'copy',
				),
				array(
					'from' => 'order_line[][0]',
					'to'   => 'order_line[][0]',
					'cast' => 'integer',
				),
				array(
					'from' => 'line_items[].product.name',
					'to'   => 'order_line[][1]',
					'cast' => 'copy',
				),
				array(
					'from' => 'order_line[][1]',
					'to'   => 'order_line[][1]',
					'cast' => 'integer',
				),
				array(
					'from' => 'line_items[].quantity',
					'to'   => 'order_line[][2].product_uom_qty',
					'cast' => 'copy',
				),
				array(
					'from' => 'order_line[][2].product_uom_qty',
					'to'   => 'order_line[][2].qty_delivered',
					'cast' => 'copy',
				),
				array(
					'from' => 'line_items[].product.sku',
					'to'   => 'order_line[][2].default_code',
					'cast' => 'copy',
				),
				array(
					'from' => 'line_items[].product.price',
					'to'   => 'order_line[][2].price_unit',
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
					'from' => 'partner_id',
					'to'   => 'order_partner_id',
					'cast' => 'copy',
				),
				array(
					'from' => '?shipping.first_name',
					'to'   => 'name[0]',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.last_name',
					'to'   => 'name[1]',
					'cast' => 'string',
				),
				array(
					'from' => 'name',
					'to'   => 'name',
					'cast' => 'concat',
				),
				array(
					'from' => '?shipping.phone',
					'to'   => 'phone',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.address_1',
					'to'   => 'street',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.address_2',
					'to'   => 'street2',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.city',
					'to'   => 'city',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.postcode',
					'to'   => 'zip',
					'cast' => 'string',
				),
				array(
					'from' => '?shipping.comment',
					'to'   => 'comment',
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
					'from' => 'order_line[][2].default_code',
					'to'   => 'internal_refs',
					'cast' => 'inherit',
				),
			),
			array(
				array(
					'from' => 'order_partner_id',
					'to'   => 'partner_id',
					'cast' => 'integer',
				),
				array(
					'from' => 'product_ids[]',
					'to'   => 'order_line[][2].product_id',
					'cast' => 'integer',
				),
			),
		),
		'workflow'      => array(
			'sync-products-by-ref',
			'contact',
			'delivery-address',
			'products-by-ref',
		),
	),
);
