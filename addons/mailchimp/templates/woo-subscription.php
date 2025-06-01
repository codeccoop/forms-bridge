<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'mailchimp-woo-subscription') {
            $index = array_search(
                'datacenter',
                array_column($data['backend']['headers'], 'name')
            );

            $dc = $data['backend']['headers'][$index]['value'];
            $data['backend']['base_url'] = preg_replace(
                '/\{dc\}/',
                $dc,
                $data['backend']['base_url']
            );

            array_splice($data['backend']['headers'], $index, 1);

            $index = array_search(
                'list_id',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            $list_id = $data['bridge']['custom_fields'][$index]['value'];
            $data['bridge']['endpoint'] = preg_replace(
                '/\{list_id\}/',
                $list_id,
                $data['bridge']['endpoint']
            );

            array_splice($data['bridge']['custom_fields'], $index, 1);

            $index = array_search(
                'tags',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['bridge']['custom_fields'][$index];

                $tags = array_filter(
                    array_map('trim', explode(',', strval($field['value'])))
                );
                for ($i = 0; $i < count($tags); $i++) {
                    $data['bridge']['custom_fields'][] = [
                        'name' => "tags[{$i}]",
                        'value' => $tags[$i],
                    ];
                }

                array_splice($data['bridge']['custom_fields'], $index, 1);
            }

            $data['bridge']['custom_fields'] = array_values(
                $data['bridge']['custom_fields']
            );
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Subscription', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will subscribe woocommerce customers to a given mailchimp audience.',
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
            'value' => '/3.0/lists/{list_id}/members',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'list_id',
            'label' => __('Audience', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'status',
            'label' => __('Subscription status', 'forms-bridge'),
            'type' => 'options',
            'options' => [
                [
                    'label' => __('Subscribed', 'forms-bridge'),
                    'value' => 'subscribed',
                ],
                [
                    'label' => __('Unsubscribed', 'forms-bridge'),
                    'value' => 'unsubscribed',
                ],
                [
                    'label' => __('Pending', 'forms-bridge'),
                    'value' => 'pending',
                ],
                [
                    'label' => __('Cleaned', 'forms-bridge'),
                    'value' => 'cleand',
                ],
                [
                    'label' => __('Transactional', 'forms-bridge'),
                    'value' => 'transactional',
                ],
            ],
            'default' => 'subscribed',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'tags',
            'label' => __('Subscription tags', 'forms-bridge'),
            'description' => __(
                'Tag names separated by commas',
                'forms-bridge'
            ),
            'type' => 'string',
        ],
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/3.0/lists/{list_id}/members',
        'custom_fields' => [
            [
                'name' => 'language',
                'value' => '$locale',
            ],
            [
                'name' => 'timestamp_signup',
                'value' => '$iso_date',
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
                    'to' => 'merge_fields.FNAME',
                    'cast' => 'string',
                ],
                [
                    'from' => '?billing.last_name',
                    'to' => 'merge_fields.LNAME',
                    'cast' => 'string',
                ],
                [
                    'from' => 'billing.email',
                    'to' => 'email_address',
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
                    'cast' => 'string',
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
