<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'brevo-woo-contacts') {
            $index = array_search(
                'listIds',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = $data['bridge']['custom_fields'][$index];

                for ($i = 0; $i < count($field['value']); $i++) {
                    $data['bridge']['custom_fields'][] = [
                        'name' => "listIds[{$i}]",
                        'value' => $field['value'][$i],
                    ];

                    $data['bridge']['mutations'][0][] = [
                        'from' => "listIds[{$i}]",
                        'to' => "listIds[{$i}]",
                        'cast' => 'integer',
                    ];
                }

                array_splice($data['bridge']['custom_fields'], $index, 1);
                $data['bridge']['custom_fields'] = array_values(
                    $data['bridge']['custom_fields']
                );
            }
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Contacts', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will subscribe woocommerce customers to a given email list.',
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
            'value' => '/v3/contacts',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'listIds',
            'label' => __('Segments', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/v3/contacts',
        'custom_fields' => [
            [
                'name' => 'attributes.LANGUAGE',
                'value' => '$locale',
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
                    'to' => 'attributes.FNAME',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.last_name',
                    'to' => 'attributes.LNAME',
                    'cast' => 'string',
                ],
                [
                    'from' => 'billing.email',
                    'to' => 'email',
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
                    'to' => 'ip_signup',
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
                    'from' => 'customer_note',
                    'to' => 'notes',
                    'cast' => 'null',
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
                    'from' => 'line_items',
                    'to' => 'line_items',
                    'cast' => 'null',
                ],
                [
                    'from' => 'currency',
                    'to' => 'currency',
                    'cast' => 'null',
                ],
            ],
        ],
    ],
];
