<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name === 'brevo-woo-contacts-doi') {
            $index = array_search(
                'includeListIds',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = $data['bridge']['custom_fields'][$index];

                for ($i = 0; $i < count($field['value']); $i++) {
                    $data['bridge']['custom_fields'][] = [
                        'name' => "includeListIds[{$i}]",
                        'value' => $field['value'][$i],
                    ];

                    $data['bridge']['mutations'][0][] = [
                        'from' => "includeListIds[{$i}]",
                        'to' => "includeListIds[{$i}]",
                        'cast' => 'integer',
                    ];
                }

                array_splice($data['bridge']['custom_fields'], $index, 1);
                $data['bridge']['custom_fields'] = array_values(
                    $data['bridge']['custom_fields']
                );
            }

            $index = array_search(
                'redirectionUrl',
                array_column($data['bridge']['custom_fields'], 'name')
            );

            if ($index !== false) {
                $field = &$data['bridge']['custom_fields'][$index];

                $field['value'] = (string) filter_var(
                    (string) $field['value'],
                    FILTER_SANITIZE_URL
                );

                $parsed = parse_url($field['value']);

                if (!isset($parsed['host'])) {
                    $site_url = get_site_url();

                    $field['value'] =
                        $site_url .
                        '/' .
                        preg_replace('/^\/+/', '', $field['value']);
                }
            }
        }

        return $data;
    },
    10,
    2
);

return [
    'title' => __('Subscription DOI', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will subscribe woocommerce customers to a given email list with a double opt in check.',
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
            'value' => '/v3/contacts/doubleOptinConfirmation',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'includeListIds',
            'label' => __('Segments', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'templateId',
            'label' => __('Double opt-in template', 'forms-bridge'),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'redirectionUrl',
            'label' => __('Redirection URL', 'forms-bridge'),
            'type' => 'string',
            'description' => __(
                'URL of the web page that user will be redirected to after clicking on the double opt in URL',
                'forms-bridge'
            ),
            'required' => true,
        ],
    ],
    'bridge' => [
        'method' => 'POST',
        'endpoint' => '/v3/contacts/doubleOptinConfirmation',
        'custom_fields' => [
            [
                'name' => 'attributes.LANGUAGE',
                'value' => '$locale',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'templateId',
                    'to' => 'templateId',
                    'cast' => 'integer',
                ],
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
