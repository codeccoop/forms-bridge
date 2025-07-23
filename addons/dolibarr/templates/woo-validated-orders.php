<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Validated Sale Orders', 'forms-bridge'),
    'description' => __(
        'Product sale order bridge template. The resulting bridge will convert WooCommerce orders into validated sale orders linked to new third parties. To work properly, <b>the bridge needs that your WooCommerce product sku values matches with the dolibarr\'s product refs.</b>.',
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
            'value' => '/api/index.php/orders',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'typent_id',
            'label' => __('Thirdparty type', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                [
                    'label' => __('Large company', 'forms-bridge'),
                    'value' => '2',
                ],
                [
                    'label' => __('Medium company', 'forms-bridge'),
                    'value' => '3',
                ],
                [
                    'label' => __('Small company', 'forms-bridge'),
                    'value' => '4',
                ],
                [
                    'label' => __('Governmental', 'forms-bridge'),
                    'value' => '5',
                ],
                [
                    'label' => __('Startup', 'forms-bridge'),
                    'value' => '1',
                ],
                [
                    'label' => __('Retailer', 'forms-bridge'),
                    'value' => '7',
                ],
                [
                    'label' => __('Private individual', 'forms-bridge'),
                    'value' => '8',
                ],
                [
                    'label' => __('Other', 'forms-bridge'),
                    'value' => '100',
                ],
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/index.php/orders',
        'custom_fields' => [
            [
                'name' => 'status',
                'value' => '1',
            ],
            [
                'name' => 'client',
                'value' => '1',
            ],
            [
                'name' => 'date',
                'value' => '$timestamp',
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
                    'to' => 'date',
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
                    'to' => 'address',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.city',
                    'to' => 'town',
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
                    'from' => '?billing.email',
                    'to' => 'shipping.email',
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
                    'to' => 'customer_note',
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
                    'from' => 'line_items[].quantity',
                    'to' => 'lines[].qty',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'line_items[].subtotal_tax.percentage',
                    'to' => 'lines[].tva_tx',
                    'cast' => 'number',
                ],
                [
                    'from' => 'line_items[].product.price',
                    'to' => 'lines[].subprice',
                    'cast' => 'number',
                ],
                [
                    'from' => 'line_items[].product.sku',
                    'to' => 'lines[].ref',
                    'cast' => 'string',
                ],
                [
                    'from' => 'line_items',
                    'to' => 'line_items',
                    'cast' => 'null',
                ],
            ],
            [
                [
                    'from' => 'socid',
                    'to' => 'order_socid',
                    'cast' => 'copy',
                ],
                [
                    'from' => '?shipping.first_name',
                    'to' => 'firstname',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.last_name',
                    'to' => 'lastname',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.phone',
                    'to' => 'phone_perso',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.address_1',
                    'to' => 'address',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.postcode',
                    'to' => 'zip',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.city',
                    'to' => 'town',
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
                    'from' => 'lines[].ref',
                    'to' => 'product_refs',
                    'cast' => 'inherit',
                ],
                [
                    'from' => 'contact_ids',
                    'to' => 'contact_ids',
                    'cast' => 'null',
                ],
            ],
            [
                [
                    'from' => '?customer_note',
                    'to' => 'note_private',
                    'cast' => 'string',
                ],
                [
                    'from' => 'order_socid',
                    'to' => 'socid',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'fk_products',
                    'to' => 'lines[].fk_product[]',
                    'cast' => 'integer',
                ],
            ],
        ],
        'workflow' => [
            'contact-socid',
            'contact-id',
            'products-by-ref',
            'validate-order',
        ],
    ],
];
