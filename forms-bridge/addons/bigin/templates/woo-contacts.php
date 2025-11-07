<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'        => __( 'Contacts', 'forms-bridge' ),
	'description'  => __(
		'Contact form template. The resulting bridge will convert woocommerce customers into contacts.',
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
			'value' => '/bigin/v2/Contacts/upsert',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'Owner.id',
			'label'       => __( 'Owner ID', 'forms-bridge' ),
			'description' => __(
				'ID of the owner user of the contact',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/bigin/v2/users',
				'finger'   => array(
					'value' => 'users[].id',
					'label' => 'users[].full_name',
				),
			),
		),
	),
	'bridge'       => array(
		'method'    => 'POST',
		'endpoint'  => '/bigin/v2/Contacts/upsert',
		'workflow'  => array( 'account-name' ),
		'mutations' => array(
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
					'from' => '?Owner',
					'to'   => 'Contact_Owner',
					'cast' => 'copy',
				),
				array(
					'from' => '?billing.company',
					'to'   => 'Account_Name',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.address_1',
					'to'   => 'Billing_Street',
					'cast' => 'copy',
				),
				array(
					'from' => '?billing.city',
					'to'   => 'Billing_City',
					'cast' => 'copy',
				),
				array(
					'from' => '?billing.state',
					'to'   => 'Billing_State',
					'cast' => 'copy',
				),
				array(
					'from' => '?billing.country',
					'to'   => 'Billing_Country',
					'cast' => 'copy',
				),
				array(
					'from' => '?billing.postcode',
					'to'   => 'Billing_Code',
					'cast' => 'copy',
				),
				array(
					'from' => '?billing.first_name',
					'to'   => 'First_Name',
					'cast' => 'string',
				),
				array(
					'from' => 'billing.last_name',
					'to'   => 'Last_Name',
					'cast' => 'string',
				),
				array(
					'from' => 'billing.email',
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
					'to'   => 'Mailing_Street',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.city',
					'to'   => 'Mailing_City',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.state',
					'to'   => 'Mailing_State',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.country',
					'to'   => 'Mailing_Country',
					'cast' => 'string',
				),
				array(
					'from' => '?billing.postcode',
					'to'   => 'Mailing_Zip',
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
					'to'   => 'ip_signup',
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
					'from' => 'customer_note',
					'to'   => 'notes',
					'cast' => 'null',
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
					'from' => 'line_items',
					'to'   => 'line_items',
					'cast' => 'null',
				),
				array(
					'from' => 'currency',
					'to'   => 'currency',
					'cast' => 'null',
				),
			),
			array(
				array(
					'from' => 'Contact_Owner',
					'to'   => 'Owner',
					'cast' => 'inherit',
				),
			),
		),
	),
);
