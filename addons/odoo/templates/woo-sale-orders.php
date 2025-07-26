<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Sale Orders', 'forms-bridge'),
    'description' => __(
        'Sale order bridge template. The resulting bridge will convert WooCommerce orders into product sale orders linked to new contacts. To work properly, <b>the bridge needs that you use your Odoo\'s product internal refrence as the WooCommerce product sku values</b>.',
        'forms-bridge'
    ),
    'integrations' => ['woo'],
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'value' => __('Woo Checkout', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'sale.order',
        ],
    ],
    'bridge' => [
        'endpoint' => 'sale.order',
        'custom_fields' => [
            [
                'name' => 'state',
                'value' => 'sale',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'id',
                    'to' => 'id',
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
                    'to' => 'date_modified',
                    'cast' => 'null',
                ],
                [
                    'from' => 'discount_total',
                    'to' => 'discount_total',
                    'cast' => 'null',
                ],
                [
                    'from' => 'discount_tax',
                    'to' => 'discount_tax',
                    'cast' => 'null',
                ],
                [
                    'from' => 'shipping_total',
                    'to' => 'shipping_total',
                    'cast' => 'null',
                ],
                [
                    'from' => 'shipping_tax',
                    'to' => 'shipping_tax',
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
                    'from' => 'total',
                    'to' => 'total',
                    'cast' => 'null',
                ],
                [
                    'from' => 'total_tax',
                    'to' => 'total_tax',
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
                    'to' => 'name[0]',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.last_name',
                    'to' => 'name[1]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'name',
                    'to' => 'name',
                    'cast' => 'concat',
                ],
                [
                    'from' => '?billing.address_1',
                    'to' => 'street',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.address_2',
                    'to' => 'street2',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.city',
                    'to' => 'city',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.postcode',
                    'to' => 'zip',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.phone',
                    'to' => 'phone',
                    'cast' => 'string',
                ],
                [
                    'from' => 'billing',
                    'to' => 'billing',
                    'cast' => 'null',
                ],
                [
                    'from' => 'payment_method',
                    'to' => 'payment_method',
                    'cast' => 'null',
                ],
                [
                    'from' => 'payment_method_title',
                    'to' => 'payment_method_title',
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
                    'to' => 'shipping.comment',
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
                [
                    'from' => 'tax_lines',
                    'to' => 'tax_lines',
                    'cast' => 'null',
                ],
                [
                    'from' => 'shipping_lines',
                    'to' => 'shipping_lines',
                    'cast' => 'null',
                ],
                [
                    'from' => 'fee_lines',
                    'to' => 'fee_lines',
                    'cast' => 'null',
                ],
                [
                    'from' => 'coupon_lines',
                    'to' => 'coupon_lines',
                    'cast' => 'null',
                ],
                [
                    'from' => 'line_items[].product.name',
                    'to' => 'order_line[][0]',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'order_line[][0]',
                    'to' => 'order_line[][0]',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'line_items[].product.name',
                    'to' => 'order_line[][1]',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'order_line[][1]',
                    'to' => 'order_line[][1]',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'line_items[].quantity',
                    'to' => 'order_line[][2].product_uom_qty',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'line_items[].product.sku',
                    'to' => 'order_line[][2].default_code',
                    'cast' => 'string',
                ],
                [
                    'from' => 'line_items[].product.price',
                    'to' => 'order_line[][2].price_unit',
                    'cast' => 'number',
                ],
                [
                    'from' => 'line_items',
                    'to' => 'line_items',
                    'cast' => 'null',
                ],
            ],
            [
                [
                    'from' => 'partner_id',
                    'to' => 'order_partner_id',
                    'cast' => 'copy',
                ],
                [
                    'from' => '?shipping.first_name',
                    'to' => 'name[0]',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.last_name',
                    'to' => 'name[1]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'name',
                    'to' => 'name',
                    'cast' => 'concat',
                ],
                [
                    'from' => '?shipping.phone',
                    'to' => 'phone',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.address_1',
                    'to' => 'street',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.address_2',
                    'to' => 'street2',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.city',
                    'to' => 'city',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.postcode',
                    'to' => 'zip',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.comment',
                    'to' => 'comment',
                    'cast' => 'string',
                ],
                [
                    'from' => 'shipping',
                    'to' => 'shipping',
                    'cast' => 'null',
                ],
            ],
            [
                [
                    'from' => 'order_line[][2].default_code',
                    'to' => 'internal_refs',
                    'cast' => 'inherit',
                ],
            ],
            [
                [
                    'from' => 'order_partner_id',
                    'to' => 'partner_id',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'product_ids[]',
                    'to' => 'order_line[][2].product_id',
                    'cast' => 'integer',
                ],
            ],
        ],
        'workflow' => ['contact', 'delivery-address', 'products-by-ref'],
    ],
];
