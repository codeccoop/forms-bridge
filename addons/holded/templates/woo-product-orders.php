<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'holded-woo-product-orders') {
            $index = array_search(
                'tags',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['bridge']['custom_fields'][$index];

                if (!empty($field['value'])) {
                    $tags = array_filter(
                        array_map('trim', explode(',', strval($field['value'])))
                    );

                    for ($i = 0; $i < count($tags); $i++) {
                        $data['bridge']['custom_fields'][] = [
                            'name' => "tags[{$i}]",
                            'value' => $tags[$i],
                        ];
                    }
                }

                array_splice($data['bridge']['custom_fields'], $index, 1);
            }
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Product Orders', 'forms-bridge'),
    'description' => __(
        'Product sale order bridge template. The resulting bridge will convert woocommerce orders into product sale orders linked to new contacts. To work propertly, <b>the bridge needs that your WooCommerce product sku values matches with the holded ones</b>.',
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
            'value' => '/api/invoicing/v1/documents/salesorder',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tags',
            'label' => __('Tags', 'forms-bridge'),
            'description' => __('Tags separated by commas', 'forms-bridge'),
            'type' => 'string',
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/invoicing/v1/documents/salesorder',
        'custom_fields' => [
            [
                'name' => 'type',
                'value' => 'client',
            ],
            [
                'name' => 'defaults.language',
                'value' => '$locale',
            ],
            [
                'name' => 'approveDoc',
                'value' => '1',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'approveDoc',
                    'to' => 'approveDoc',
                    'cast' => 'boolean',
                ],
                [
                    'from' => 'id',
                    'to' => 'customFields.wp_order_id',
                    'cast' => 'integer',
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
                    'cast' => 'string',
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
                    'to' => 'customFields.wp_customer_id',
                    'cast' => 'integer',
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
                    'to' => 'billAddress.address',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.city',
                    'to' => 'billAddress.city',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.postcode',
                    'to' => 'billAddress.postalCode',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.state',
                    'to' => 'billAddress.province',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.country',
                    'to' => 'billAddress.country',
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
                    'from' => '?shipping.address_1',
                    'to' => 'shippingAddresses[0].address',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.postcode',
                    'to' => 'shippingAddresses[0].postalCode',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.city',
                    'to' => 'shippingAddresses[0].city',
                    'cast' => 'string',
                ],
                [
                    'from' => '?shipping.state',
                    'to' => 'shippingAddresses[0].province',
                    'cast' => 'string',
                ],
                [
                    'from' => 'shipping.country',
                    'to' => 'shippingAddresses[0].country',
                    'cast' => 'string',
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
                    'to' => 'notes',
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
                    'from' => 'line_items[].name',
                    'to' => 'items[].name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'line_items[].subtotal',
                    'to' => 'items[].subtotal',
                    'cast' => 'number',
                ],
                [
                    'from' => 'line_items[].quantity',
                    'to' => 'items[].units',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'line_items[].product.sku',
                    'to' => 'items[].sku',
                    'cast' => 'string',
                ],
                [
                    'from' => 'line_items',
                    'to' => 'line_items',
                    'cast' => 'null',
                ],
                [
                    'from' => 'tags',
                    'to' => 'order_tags',
                    'cast' => 'inherit',
                ],
            ],
            [
                [
                    'from' => 'order_tags',
                    'to' => 'tags',
                    'cast' => 'inherit',
                ],
            ],
        ],
        'workflow' => ['holded-contact-id'],
    ],
];
