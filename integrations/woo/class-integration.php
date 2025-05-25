<?php

namespace FORMS_BRIDGE\WOO;

use FORMS_BRIDGE\Forms_Bridge;
use FORMS_BRIDGE\Integration as BaseIntegration;
use WC_Session_Handler;
use WC_Customer;

class Integration extends BaseIntegration
{
    private static $order_id;
    private static $order_status;

    private const order_data_schema = [
        'type' => 'object',
        'properties' => [
            'id' => ['type' => 'integer'],
            'parent_id' => ['type' => 'integer'],
            'status' => ['type' => 'string'],
            'currency' => ['type' => 'string'],
            'version' => ['type' => 'string'],
            'prices_include_tax' => ['type' => 'boolean'],
            'date_created' => ['type' => 'string'],
            'date_modified' => ['type' => 'string'],
            'discount_total' => ['type' => 'number'],
            'discount_tax' => ['type' => 'number'],
            'shipping_total' => ['type' => 'number'],
            'shipping_tax' => ['type' => 'number'],
            'cart_tax' => ['type' => 'number'],
            'total' => ['type' => 'number'],
            'total_tax' => ['type' => 'number'],
            'customer_id' => ['type' => 'integer'],
            'order_key' => ['type' => 'string'],
            'billing' => [
                'type' => 'object',
                'properties' => [
                    'first_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'],
                    'company' => ['type' => 'string'],
                    'address_1' => ['type' => 'string'],
                    'address_2' => ['type' => 'string'],
                    'city' => ['type' => 'string'],
                    'state' => ['type' => 'string'],
                    'postcode' => ['type' => 'string'],
                    'country' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                ],
                'additionalProperties' => true,
            ],
            'shipping' => [
                'type' => 'object',
                'properties' => [
                    'first_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'],
                    'company' => ['type' => 'string'],
                    'address_1' => ['type' => 'string'],
                    'address_2' => ['type' => 'string'],
                    'city' => ['type' => 'string'],
                    'state' => ['type' => 'string'],
                    'postcode' => ['type' => 'string'],
                    'country' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                ],
                'additionalProperties' => true,
            ],
            'payment_method' => ['type' => 'string'],
            'payment_method_title' => ['type' => 'string'],
            'transaction_id' => ['type' => 'string'],
            'customer_ip_address' => ['type' => 'string'],
            'customer_user_agent' => ['type' => 'string'],
            'created_via' => ['type' => 'string'],
            'customer_note' => ['type' => 'string'],
            'date_completed' => ['type' => 'string'],
            'date_paid' => ['type' => 'string'],
            'cart_hash' => ['type' => 'string'],
            'order_stock_reduced' => ['type' => 'boolean'],
            'download_permissions_granted' => ['type' => 'boolean'],
            'new_order_email_sent' => ['type' => 'boolean'],
            'recorded_sales' => ['type' => 'boolean'],
            'recorded_coupon_usage_counts' => ['type' => 'boolean'],
            'number' => ['type' => 'integer'],
            // 'meta_data' => [
            //     'type' => 'array',
            //     'items' => [
            //         'type' => 'object',
            //         'properties' => [
            //             'id' => ['type' => 'integer'],
            //             'key' => ['type' => 'string'],
            //             'value' => ['type' => 'string'],
            //         ],
            //         'required' => ['id', 'key', 'value'],
            //         'additionalProperties' => false,
            //     ],
            //     'additionalItems' => true,
            // ],
            'line_items' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'product_id' => ['type' => 'integer'],
                        'quantity' => ['type' => 'integer'],
                        'subtotal' => ['type' => 'number'],
                        'subtotal_tax' => ['type' => 'number'],
                        'tax_class' => ['type' => 'string'],
                        'taxes' => [
                            'type' => 'object',
                            'properties' => [
                                'subtotal' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'number'],
                                    'additionalItems' => true,
                                ],
                                'total' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'number'],
                                    'additionalItems' => true,
                                ],
                            ],
                        ],
                        'total' => ['type' => 'number'],
                        'total_tax' => ['type' => 'number'],
                        'variation_id' => ['type' => 'integer'],
                        'product' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'parent_id' => ['type' => 'integer'],
                                'sku' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'slug' => ['type' => 'string'],
                                'price' => ['type' => 'number'],
                                'sale_price' => ['type' => 'number'],
                                'regular_price' => ['type' => 'number'],
                            ],
                        ],
                        // 'meta_data' => [
                        // 	'type' => 'array',
                        // 	'items' => [
                        // 		'type' => 'object',
                        // 		'properties' => [
                        // 			'id' => ['type' => 'integer'],
                        // 			'key' => ['type' => 'string'],
                        // 			'value' => ['type' => 'string'],
                        // 		],
                        // 	],
                        // ],
                    ],
                ],
                'additionalItems' => true,
                'minItems' => 1,
            ],
            'tax_lines' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'compound' => ['type' => 'boolean'],
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        // 'meta_data' => [
                        //     'type' => 'array',
                        //     'items' => [
                        //         'type' => 'object',
                        //         'properties' => [
                        //             'id' => ['type' => 'integer'],
                        //             'key' => ['type' => 'string'],
                        //             'value' => ['type' => 'string'],
                        //         ],
                        //     ],
                        //     'additionalItems' => true,
                        // ],
                        'rate_code' => ['type' => 'string'],
                        'rate_id' => ['type' => 'integer'],
                        'rate_percent' => ['type' => 'number'],
                        'shipping_tax_total' => ['type' => 'number'],
                        'tax_total' => ['type' => 'number'],
                    ],
                ],
                'additionalItems' => true,
            ],
            'shipping_lines' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'instance_id' => ['type' => 'integer'],
                        'method_id' => ['type' => 'string'],
                        'method_title' => ['type' => 'string'],
                        'name' => ['type' => 'string'],
                        'order_id' => ['type' => 'integer'],
                        'tax_status' => ['type' => 'string'],
                        'taxes' => [
                            'type' => 'object',
                            'properties' => [
                                'total' => ['type' => 'number'],
                                'subtotal' => ['type' => 'number'],
                            ],
                            'required' => ['total'],
                        ],
                        'total' => ['type' => 'number'],
                        'total_tax' => ['type' => 'number'],
                        // 'meta_data' => [
                        // 	'type' => 'array',
                        // 	'items' => [
                        // 		'type' => 'object',
                        // 		'properties' => [
                        // 			'id' => ['type' => 'integer'],
                        // 			'key' => ['type' => 'string'],
                        // 			'value' => ['type' => 'string'],
                        // 		],
                        // 	],
                        // ],
                    ],
                ],
                'additionalItems' => true,
            ],
            'fee_lines' => [
                'type' => 'array',
                'items' => [],
                'additionalItems' => true,
            ],
            'coupon_lines' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'order_id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'code' => ['type' => 'string'],
                        'discount' => ['type' => 'number'],
                        'discount_tax' => ['type' => 'number'],
                        // 'meta_data' => [
                        // 	'type' => 'array',
                        // 	'items' => [
                        // 		'type' => 'object',
                        // 		'properties' => [
                        // 			'id' => ['type' => 'integer'],
                        // 			'key' => ['type' => 'string'],
                        // 			'value' => ['type' => 'string'],
                        // 		],
                        // 	],
                        // ],
                    ],
                ],
                'additionalItems' => true,
            ],
            'customer_note' => ['type' => 'string'],
        ],
    ];

    public function init()
    {
        add_action(
            'woocommerce_order_status_changed',
            static function ($order_id, $old_status, $new_status) {
                $trigger_submission = apply_filters(
                    'forms_bridge_woo_trigger_submission',
                    $new_status === 'completed',
                    $order_id,
                    $new_status,
                    $old_status
                );

                if ($trigger_submission) {
                    self::$order_id = $order_id;
                    self::$order_status = $new_status;

                    Forms_Bridge::do_submission();
                }
            },
            10,
            3
        );
    }

    public function form()
    {
        if (empty(self::$order_id)) {
            return;
        }

        return $this->get_form_by_id('checkout');
    }

    public function get_form_by_id($form_id)
    {
        if ($form_id !== 'checkout') {
            return;
        }

        WC()->session = new WC_Session_Handler();
        WC()->customer = new WC_Customer();

        return apply_filters(
            'forms_bridge_form_data',
            [
                '_id' => 'woo:1',
                'id' => '1',
                'title' => __('Woo Checkout', 'forms-bridge'),
                'bridges' => apply_filters('forms_bridge_bridges', [], 'woo:1'),
                'fields' => $this->serialize_order_fields(),
            ],
            WC()->checkout,
            'woo'
        );
    }

    public function forms()
    {
        return [$this->get_form_by_id('checkout')];
    }

    public function create_form($data)
    {
        return;
    }

    public function remove_form($form_id)
    {
        return;
    }

    public function submission_id()
    {
        if (self::$order_id) {
            return (string) self::$order_id;
        }
    }

    public function submission($raw)
    {
        if (empty(self::$order_id)) {
            return;
        }

        return $this->serialize_order(self::$order_id);
    }

    public function uploads()
    {
        return [];
    }

    private function serialize_order_fields()
    {
        $checkout_fields = WC()->checkout->checkout_fields;

        $fields = [];
        foreach (
            self::order_data_schema['properties']
            as $name => $field_schema
        ) {
            $fields[] = self::decorate_order_field($name, $field_schema);
        }

        foreach ($checkout_fields['billing'] as $name => $data) {
            $name = str_replace('billing_', '', $name);
            if (isset(self::order_data_schema['billing'][$name])) {
                continue;
            }

            $index = array_search('billing', array_column($fields, 'name'));

            $billing_field = &$fields[$index];
            $billing_field['schema']['properties'][$name] = [
                'type' => 'string',
            ];
        }

        foreach ($checkout_fields['shipping'] as $name => $data) {
            $name = str_replace('shipping_', '', $name);
            if (isset(self::order_data_schema['shipping'][$name])) {
                continue;
            }

            $index = array_search('shipping', array_column($fields, 'name'));

            $shipping_field = &$fields[$index];
            $shipping_field['schema']['properties'][$name] = [
                'type' => 'string',
            ];
        }

        return $fields;
    }

    private function decorate_order_field($name, $schema, $is_multi = false)
    {
        switch ($schema['type']) {
            case 'string':
                $field_type = 'text';
                break;
            case 'number':
            case 'integer':
                $field_type = 'number';
                break;
            case 'boolean':
                $field_type = 'boolean';
                break;
            case 'array':
                $field_type = 'options';
                break;
            default:
                $field_type = $schema['type'];
        }

        return [
            'id' => null,
            'name' => $name,
            'label' => $name,
            'type' => $field_type,
            'required' => true,
            'is_file' => false,
            'is_multi' => $schema['type'] === 'array',
            'conditional' => false,
            'schema' => $schema,
        ];
    }

    private function serialize_order($order_id)
    {
        $order = wc_get_order($order_id);
        if (empty($order)) {
            return;
        }

        $checkout = WC()->checkout;

        $data = $order->get_data();
        unset($data['meta_data']);

        $checkout_fields = $checkout->checkout_fields;
        foreach (array_keys($checkout_fields['billing']) as $name) {
            $unprefixed = str_replace('billing_', '', $name);
            if (!isset($data['billing'][$unprefixed])) {
                $data['billing'][$unprefixed] = $checkout->get_value($name);
            }
        }

        foreach (array_keys($checkout_fields['shipping']) as $name) {
            $unprefixed = str_replace('shipping_', '', $name);
            if (!isset($data['shipping'][$unprefixed])) {
                $data['shipping'][$unprefixed] = $checkout->get_value($name);
            }
        }

        $data['customer_note'] = $checkout->get_value('customer_note');

        $line_items = [];
        foreach ($data['line_items'] as $line_item) {
            $item_data = $line_item->get_data();
            unset($item_data['meta_data']);

            $product = $line_item->get_product();
            $item_data['product'] = [
                'id' => $product->get_id(),
                'parent_id' => $product->get_parent_id(),
                'slug' => $product->get_slug(),
                'sku' => $product->get_sku(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'sale_price' => $product->get_sale_price(),
                'regular_price' => $product->get_regular_price(),
            ];

            $line_items[] = $item_data;
        }

        $data['line_items'] = $line_items;

        $tax_lines = [];
        foreach ($data['tax_lines'] as $tax_line) {
            $line_data = $tax_line->get_data();
            unset($line_data['meta_data']);
            $tax_lines[] = $line_data;
        }

        $data['tax_lines'] = $tax_lines;

        $shipping_lines = [];
        foreach ($data['shipping_lines'] as $shipping_line) {
            $line_data = $shipping_line->get_data();
            unset($line_data['meta_data']);
            $shipping_lines[] = $line_data;
        }

        $data['shipping_lines'] = $shipping_lines;

        $coupon_lines = [];
        foreach ($data['coupon_lines'] ?? [] as $coupon_line) {
            $line_data = $coupon_line->get_data();
            unset($line_data['meta_data']);
            $coupon_lines[] = $line_data;
        }

        $data['coupon_lines'] = $coupon_lines;

        $fee_lines = [];
        foreach ($data['fee_lines'] ?? [] as $fee_line) {
            $line_data = $fee_line->get_data();
            unset($line_data['meta_data']);
            $fee_lines[] = $line_data;
        }

        $data['fee_lines'] = $fee_lines;

        return rest_sanitize_value_from_schema($data, self::order_data_schema);
    }
}
