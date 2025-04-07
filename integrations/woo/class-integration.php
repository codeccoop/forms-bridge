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

    public function init()
    {
        add_action(
            'woocommerce_order_status_changed',
            static function ($order_id, $old_status, $new_status) {
                self::$order_id = $order_id;
                self::$order_status = $new_status;

                $trigger_submission = apply_filters(
                    'forms_bridge_woo_trigger_submission',
                    $new_status === 'completed',
                    $order_id,
                    $new_status,
                    $old_status
                );

                if ($trigger_submission) {
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
        if (self::$order_status !== 'completed') {
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
        $fields = WC()->checkout->checkout_fields;

        return apply_filters(
            'forms_bridge_form_data',
            [
                '_id' => 'woo:1',
                'id' => '1',
                'title' => __('Woo Checkout', 'forms-bridge'),
                'bridges' => apply_filters('forms_bridge_bridges', [], 'woo:1'),
                'fields' => $this->serialize_checkout_fields($fields),
            ],
            ['id' => 'checkout', 'fields' => $fields],
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

    private function serialize_checkout_fields($fields)
    {
        $fields_data = [];
        foreach ($fields['billing'] as $name => $data) {
            $fields_data[] = $this->serialize_checkout_field($name, $data);
        }

        foreach ($fields['shipping'] as $name => $data) {
            $fields_data[] = $this->serialize_checkout_field($name, $data);
        }

        foreach ($fields['account'] as $name => $data) {
            $fields_data[] = $this->serialize_checkout_field($name, $data);
        }

        foreach ($fields['order'] as $name => $data) {
            $fields_data[] = $this->serialize_checkout_field($name, $data);
        }

        $fields_data[] = [
            'id' => null,
            'name' => 'items',
            'label' => 'items',
            'type' => 'items',
            'required' => true,
            'is_file' => false,
            'is_multi' => true,
            'conditional' => false,
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'quantity' => ['type' => 'integer'],
                        'type' => ['type' => 'string'],
                        'product' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'sku' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'price' => ['type' => 'number'],
                            ],
                        ],
                    ],
                ],
                'additionalItems' => true,
            ],
        ];

        return $fields_data;
    }

    private function serialize_checkout_field($name, $data)
    {
        $field_data = [
            'id' => null,
            'name' => $name,
            'label' => $data['label'],
            'type' => 'text',
            'required' => $data['required'] ?? false,
            'is_file' => false,
            'is_multi' => false,
            'conditional' => false,
            'schema' => ['type' => 'string'],
        ];

        if ($name === 'coupon_codes') {
            $field_data['schema'] = [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'additionalItems' => true,
            ];
        }

        return apply_filters(
            'forms_bridge_form_field_data',
            $field_data,
            array_merge($data, ['name' => $name]),
            'woo'
        );
    }

    private function serialize_order($order_id)
    {
        $order = wc_get_order($order_id);
        if (empty($order)) {
            return;
        }

        return [
            'id' => $order->get_id(),
            'title' => $order->get_title(),
            'total' => $order->get_total(),
            'subtotal' => $order->get_subtotal(),
            'total_tax' => $order->get_total_tax(),
            'total_fees' => $order->get_total_fees(),
            'total_shipping' => $order->get_total_shipping(),
            'total_discount' => $order->get_total_discount(),
            'date' => strval($order->get_date_modified()),
            'status' => $order->get_status(),
            'coupon_codes' => $order->get_coupon_codes(),
            'payment_method' => $order->get_payment_method_title(),
            'currency' => $order->get_currency(),
            'customer_id' => $order->get_customer_id(),
            'customer_note' => $order->get_customer_note(),
            'billing_first_name' => $order->get_billing_first_name(),
            'billing_last_name' => $order->get_billing_last_name(),
            'billing_email' => $order->get_billing_email(),
            'billing_phone' => $order->get_billing_phone(),
            'billing_address_1' => $order->get_billing_address_1(),
            'billing_address_2' => $order->get_billing_address_2(),
            'billing_city' => $order->get_billing_city(),
            'billing_state' => $order->get_billing_state(),
            'billing_postcode' => $order->get_billing_postcode(),
            'billing_country' => $order->get_billing_country(),
            'billing_company' => $order->get_billing_company(),
            'shipping_first_name' => $order->get_shipping_first_name(),
            'shipping_last_name' => $order->get_shipping_last_name(),
            'shipping_phone' => $order->get_shipping_phone(),
            'shipping_address_1' => $order->get_shipping_address_1(),
            'shipping_address_2' => $order->get_shipping_address_2(),
            'shipping_city' => $order->get_shipping_city(),
            'shipping_state' => $order->get_shipping_state(),
            'shipping_postcode' => $order->get_shipping_postcode(),
            'shipping_country' => $order->get_shipping_country(),
            'shipping_company' => $order->get_shipping_company(),
            'shipping_method' => $order->get_shipping_method(),
            'items' => $this->get_order_items($order),
        ];
    }

    private function get_order_items($order)
    {
        return array_values(
            array_map(function ($item) {
                $product = $item->get_product();

                return [
                    'id' => $item->get_id(),
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'type' => $item->get_type(),
                    'product' => [
                        'id' => $product->get_id(),
                        'sku' => $product->get_sku(),
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                    ],
                ];
            }, $order->get_items())
        );
    }
}
