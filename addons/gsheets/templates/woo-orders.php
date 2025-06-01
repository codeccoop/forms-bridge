<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('WC Orders', 'forms-bridge'),
    'description' => __(
        'Sale order bridge template. The resulting bridge will convert WooCommerce orders into new lines on a Google Spreadsheet with order and customer information.',
        'forms-bridge'
    ),
    'integrations' => ['woo'],
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'value' => __('Woo Checkout', 'forms-bridge'),
        ],
    ],
    'bridge' => [
        'mutations' => [
            [
                [
                    'from' => 'id',
                    'to' => 'Order ID',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'line_items[].product.name',
                    'to' => 'Name[][0]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'line_items[].quantity',
                    'to' => 'Name[][1]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'Name',
                    'to' => 'Name[]',
                    'cast' => 'concat',
                ],
                [
                    'from' => 'Name',
                    'to' => 'Name',
                    'cast' => 'csv',
                ],
                [
                    'from' => 'line_items',
                    'to' => 'line_items',
                    'cast' => 'null',
                ],
                [
                    'from' => 'parent_id',
                    'to' => 'parent_id',
                    'cast' => 'null',
                ],
                [
                    'from' => 'status',
                    'to' => 'status',
                    'cast' => 'null',
                ],
                [
                    'from' => 'version',
                    'to' => 'version',
                    'cast' => 'null',
                ],
                [
                    'from' => 'currency',
                    'to' => 'currency',
                    'cast' => 'null',
                ],
                [
                    'from' => 'tax_lines',
                    'to' => 'tax_lines',
                    'cast' => 'null',
                ],
                [
                    'from' => 'fee_lines',
                    'to' => 'fee_lines',
                    'cast' => 'null',
                ],
                [
                    'from' => 'prices_include_tax',
                    'to' => 'prices_include_tax',
                    'cast' => 'null',
                ],
                [
                    'from' => 'date_created',
                    'to' => 'date_created',
                    'cast' => 'null',
                ],
                [
                    'from' => 'date_modified',
                    'to' => 'Date',
                    'cast' => 'string',
                ],
                [
                    'from' => 'total',
                    'to' => 'Total',
                    'cast' => 'number',
                ],
                [
                    'from' => 'total_tax',
                    'to' => 'total_tax',
                    'cast' => 'null',
                ],
                [
                    'from' => 'shipping_lines[].name',
                    'to' => 'Shipping',
                    'cast' => 'string',
                ],
                [
                    'from' => 'Shipping',
                    'to' => 'Shipping',
                    'cast' => 'csv',
                ],
                [
                    'from' => 'shipping_lines',
                    'to' => 'shipping_lines',
                    'cast' => 'null',
                ],
                [
                    'from' => 'shipping_total',
                    'to' => 'Shipping Total',
                    'cast' => 'number',
                ],
                [
                    'from' => 'shipping_tax',
                    'to' => 'shipping_tax',
                    'cast' => 'null',
                ],
                [
                    'from' => 'coupon_lines[].code',
                    'to' => 'Coupons',
                    'cast' => 'string',
                ],
                [
                    'from' => 'Coupons',
                    'to' => 'Coupons',
                    'cast' => 'csv',
                ],
                [
                    'from' => 'coupon_lines',
                    'to' => 'coupon_lines',
                    'cast' => 'null',
                ],
                [
                    'from' => 'discount_total',
                    'to' => 'Discount Total',
                    'cast' => 'number',
                ],
                [
                    'from' => 'discount_tax',
                    'to' => 'discount_tax',
                    'cast' => 'null',
                ],
                [
                    'from' => 'cart_total',
                    'to' => 'cart_total',
                    'cast' => 'null',
                ],
                [
                    'from' => 'cart_tax',
                    'to' => 'cart_tax',
                    'cast' => 'null',
                ],
                [
                    'from' => 'customer_id',
                    'to' => 'customer_id',
                    'cast' => 'null',
                ],
                [
                    'from' => 'order_key',
                    'to' => 'order_key',
                    'cast' => 'null',
                ],
                [
                    'from' => 'billing.first_name',
                    'to' => 'First Name',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.last_name',
                    'to' => 'Last Name',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.email',
                    'to' => 'Email',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.phone',
                    'to' => 'Phone',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.address_1',
                    'to' => 'Address',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.address_2',
                    'to' => 'Address 2',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.city',
                    'to' => 'City',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.postcode',
                    'to' => 'Postal Code',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.state',
                    'to' => 'State',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.country',
                    'to' => 'Country',
                    'cast' => 'string',
                ],
                [
                    'from' => 'billing',
                    'to' => 'billing',
                    'cast' => 'null',
                ],
                [
                    'from' => 'shipping',
                    'to' => 'shipping',
                    'cast' => 'null',
                ],
                [
                    'from' => 'payment_method',
                    'to' => 'payment_method',
                    'cast' => 'null',
                ],
                [
                    'from' => 'payment_method_title',
                    'to' => 'Payment Method',
                    'cast' => 'null',
                ],
                [
                    'from' => 'transaction_id',
                    'to' => 'transaction_id',
                    'cast' => 'null',
                ],
                [
                    'from' => 'customer_ip_address',
                    'to' => 'customer_ip_address',
                    'cast' => 'null',
                ],
                [
                    'from' => 'customer_user_agent',
                    'to' => 'customer_user_agent',
                    'cast' => 'null',
                ],
                [
                    'from' => 'created_via',
                    'to' => 'created_via',
                    'cast' => 'null',
                ],
                [
                    'from' => '?customer_note',
                    'to' => 'Note',
                    'cast' => 'string',
                ],
                [
                    'from' => 'date_completed',
                    'to' => 'date_completed',
                    'cast' => 'null',
                ],
                [
                    'from' => 'date_paid',
                    'to' => 'date_paid',
                    'cast' => 'null',
                ],
                [
                    'from' => 'cart_hash',
                    'to' => 'cart_hash',
                    'cast' => 'null',
                ],
                [
                    'from' => 'order_stock_reduced',
                    'to' => 'order_stock_reduced',
                    'cast' => 'null',
                ],
                [
                    'from' => 'download_permissions_granted',
                    'to' => 'download_permissions_granted',
                    'cast' => 'null',
                ],
                [
                    'from' => 'new_order_email_sent',
                    'to' => 'new_order_email_sent',
                    'cast' => 'null',
                ],
                [
                    'from' => 'recorded_sales',
                    'to' => 'recorded_sales',
                    'cast' => 'null',
                ],
                [
                    'from' => 'recorded_coupon_usage_counts',
                    'to' => 'recorded_coupon_usage_counts',
                    'cast' => 'null',
                ],
                [
                    'from' => 'number',
                    'to' => 'number',
                    'cast' => 'null',
                ],
            ],
        ],
    ],
];
