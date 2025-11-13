<?php
/**
 * Class Woo_Integration
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE\WOO;

use DivisionByZeroError;
use TypeError;
use FORMS_BRIDGE\Forms_Bridge;
use FBAPI;
use FORMS_BRIDGE\Integration as BaseIntegration;
use WC_Session_Handler;
use WC_Customer;

/**
 * WooCommerce integration class.
 */
class Woo_Integration extends BaseIntegration {
	/**
	 * Handles integration name.
	 *
	 * @var string
	 */
	const NAME = 'woo';

	/**
	 * Handles integration title.
	 *
	 * @var string
	 */
	const TITLE = 'WooCommerce';

	/**
	 * The integration will store order's bridged result as a custom field. This const handles the custom field name.
	 *
	 * @var string
	 */
	private const ORDER_BRIDGED_CF = 'forms_bridge_woo_order_bridge';

	/**
	 * Handles the current order ID.
	 *
	 * @var integer|null
	 */
	private static $order_id;

	/**
	 * Handles the WooCommerce order data json schema.
	 *
	 * @var array
	 */
	private static $order_data_schema = array(
		'type'                 => 'object',
		'properties'           => array(
			'id'                           => array( 'type' => 'integer' ),
			'parent_id'                    => array( 'type' => 'integer' ),
			'status'                       => array( 'type' => 'string' ),
			'currency'                     => array( 'type' => 'string' ),
			'version'                      => array( 'type' => 'string' ),
			'prices_include_tax'           => array( 'type' => 'boolean' ),
			'date_created'                 => array( 'type' => 'string' ),
			'date_modified'                => array( 'type' => 'string' ),
			'discount_total'               => array( 'type' => 'number' ),
			'discount_tax'                 => array(
				'type'       => 'object',
				'properties' => array(
					'amount'     => array( 'type' => 'number' ),
					'rate'       => array( 'type' => 'number' ),
					'percentage' => array( 'type' => 'number' ),
				),
			),
			'shipping_total'               => array( 'type' => 'number' ),
			'shipping_tax'                 => array(
				'type'       => 'object',
				'properties' => array(
					'amount'     => array( 'type' => 'number' ),
					'rate'       => array( 'type' => 'number' ),
					'percentage' => array( 'type' => 'number' ),
				),
			),
			'cart_total'                   => array( 'type' => 'number' ),
			'cart_tax'                     => array(
				'type'       => 'object',
				'properties' => array(
					'amount'     => array( 'type' => 'number' ),
					'rate'       => array( 'type' => 'number' ),
					'percentage' => array( 'type' => 'number' ),
				),
			),
			'total'                        => array( 'type' => 'number' ),
			'total_tax'                    => array(
				'type'       => 'object',
				'properties' => array(
					'amount'     => array( 'type' => 'number' ),
					'rate'       => array( 'type' => 'number' ),
					'percentage' => array( 'type' => 'number' ),
				),
			),
			'customer_id'                  => array( 'type' => 'integer' ),
			'order_key'                    => array( 'type' => 'string' ),
			'billing'                      => array(
				'type'                 => 'object',
				'properties'           => array(
					'first_name' => array( 'type' => 'string' ),
					'last_name'  => array( 'type' => 'string' ),
					'company'    => array( 'type' => 'string' ),
					'address_1'  => array( 'type' => 'string' ),
					'address_2'  => array( 'type' => 'string' ),
					'city'       => array( 'type' => 'string' ),
					'state'      => array( 'type' => 'string' ),
					'postcode'   => array( 'type' => 'string' ),
					'country'    => array( 'type' => 'string' ),
					'email'      => array( 'type' => 'string' ),
					'phone'      => array( 'type' => 'string' ),
				),
				'additionalProperties' => true,
			),
			'shipping'                     => array(
				'type'                 => 'object',
				'properties'           => array(
					'first_name' => array( 'type' => 'string' ),
					'last_name'  => array( 'type' => 'string' ),
					'company'    => array( 'type' => 'string' ),
					'address_1'  => array( 'type' => 'string' ),
					'address_2'  => array( 'type' => 'string' ),
					'city'       => array( 'type' => 'string' ),
					'state'      => array( 'type' => 'string' ),
					'postcode'   => array( 'type' => 'string' ),
					'country'    => array( 'type' => 'string' ),
					'phone'      => array( 'type' => 'string' ),
				),
				'additionalProperties' => true,
			),
			'payment_method'               => array( 'type' => 'string' ),
			'payment_method_title'         => array( 'type' => 'string' ),
			'transaction_id'               => array( 'type' => 'string' ),
			'customer_ip_address'          => array( 'type' => 'string' ),
			'customer_user_agent'          => array( 'type' => 'string' ),
			'created_via'                  => array( 'type' => 'string' ),
			'customer_note'                => array( 'type' => 'string' ),
			'date_completed'               => array( 'type' => 'string' ),
			'date_paid'                    => array( 'type' => 'string' ),
			'cart_hash'                    => array( 'type' => 'string' ),
			'order_stock_reduced'          => array( 'type' => 'boolean' ),
			'download_permissions_granted' => array( 'type' => 'boolean' ),
			'new_order_email_sent'         => array( 'type' => 'boolean' ),
			'recorded_sales'               => array( 'type' => 'boolean' ),
			'recorded_coupon_usage_counts' => array( 'type' => 'boolean' ),
			'number'                       => array( 'type' => 'integer' ),
			// 'meta_data' => [
			// 'type' => 'array',
			// 'items' => [
			// 'type' => 'object',
			// 'properties' => [
			// 'id' => ['type' => 'integer'],
			// 'key' => ['type' => 'string'],
			// 'value' => ['type' => 'string'],
			// ],
			// 'required' => ['id', 'key', 'value'],
			// 'additionalProperties' => false,
			// ],
			// 'additionalItems' => true,
			// ],
			'line_items'                   => array(
				'type'            => 'array',
				'items'           => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'order_id'     => array( 'type' => 'integer' ),
						'name'         => array( 'type' => 'string' ),
						'product_id'   => array( 'type' => 'integer' ),
						'variation_id' => array( 'type' => 'integer' ),
						'quantity'     => array( 'type' => 'integer' ),
						'tax_class'    => array( 'type' => 'string' ),
						'subtotal'     => array( 'type' => 'number' ),
						'subtotal_tax' => array(
							'type'       => 'object',
							'properties' => array(
								'amount'     => array( 'type' => 'number' ),
								'rate'       => array( 'type' => 'number' ),
								'percentage' => array( 'type' => 'number' ),
							),
						),
						'total'        => array( 'type' => 'number' ),
						'total_tax'    => array(
							'type'       => 'object',
							'properties' => array(
								'amount'     => array( 'type' => 'number' ),
								'rate'       => array( 'type' => 'number' ),
								'percentage' => array( 'type' => 'number' ),
							),
						),
						'taxes'        => array(
							'type'       => 'object',
							'properties' => array(
								'subtotal' => array(
									'type'            => 'array',
									'items'           => array( 'type' => 'number' ),
									'additionalItems' => true,
								),
								'total'    => array(
									'type'            => 'array',
									'items'           => array( 'type' => 'number' ),
									'additionalItems' => true,
								),
							),
						),
						'product'      => array(
							'type'       => 'object',
							'properties' => array(
								'id'             => array( 'type' => 'integer' ),
								'parent_id'      => array( 'type' => 'integer' ),
								'sku'            => array( 'type' => 'string' ),
								'name'           => array( 'type' => 'string' ),
								'slug'           => array( 'type' => 'string' ),
								'price'          => array( 'type' => 'number' ),
								'sale_price'     => array( 'type' => 'number' ),
								'regular_price'  => array( 'type' => 'number' ),
								'stock_quantity' => array( 'type' => 'number' ),
								'stock_status'   => array( 'type' => 'string' ),
							),
						),
						// 'meta_data' => [
						// 'type' => 'array',
						// 'items' => [
						// 'type' => 'object',
						// 'properties' => [
						// 'id' => ['type' => 'integer'],
						// 'key' => ['type' => 'string'],
						// 'value' => ['type' => 'string'],
						// ],
						// ],
						// ],
					),
				),
				'additionalItems' => true,
				'minItems'        => 1,
			),
			'tax_lines'                    => array(
				'type'            => 'array',
				'items'           => array(
					'type'       => 'object',
					'properties' => array(
						'id'                 => array( 'type' => 'integer' ),
						'order_id'           => array( 'type' => 'integer' ),
						'name'               => array( 'type' => 'string' ),
						'rate_code'          => array( 'type' => 'string' ),
						'rate_id'            => array( 'type' => 'integer' ),
						'label'              => array( 'type' => 'string' ),
						'compound'           => array( 'type' => 'boolean' ),
						'tax_total'          => array( 'type' => 'number' ),
						'shipping_tax_total' => array( 'type' => 'number' ),
						'rate_percent'       => array( 'type' => 'number' ),
						// 'meta_data' => [
						// 'type' => 'array',
						// 'items' => [
						// 'type' => 'object',
						// 'properties' => [
						// 'id' => ['type' => 'integer'],
						// 'key' => ['type' => 'string'],
						// 'value' => ['type' => 'string'],
						// ],
						// ],
						// 'additionalItems' => true,
						// ],
					),
				),
				'additionalItems' => true,
			),
			'shipping_lines'               => array(
				'type'            => 'array',
				'items'           => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'order_id'     => array( 'type' => 'integer' ),
						'name'         => array( 'type' => 'string' ),
						'method_id'    => array( 'type' => 'string' ),
						'method_title' => array( 'type' => 'string' ),
						'instance_id'  => array( 'type' => 'integer' ),
						'total'        => array( 'type' => 'number' ),
						'total_tax'    => array(
							'type'       => 'object',
							'properties' => array(
								'amount'     => array( 'type' => 'number' ),
								'rate'       => array( 'type' => 'number' ),
								'percentage' => array( 'type' => 'number' ),
							),
						),
						'tax_status'   => array( 'type' => 'string' ),
						'taxes'        => array(
							'type'       => 'object',
							'properties' => array(
								'total'    => array( 'type' => 'number' ),
								'subtotal' => array( 'type' => 'number' ),
							),
							'required'   => array( 'total' ),
						),
						// 'meta_data' => [
						// 'type' => 'array',
						// 'items' => [
						// 'type' => 'object',
						// 'properties' => [
						// 'id' => ['type' => 'integer'],
						// 'key' => ['type' => 'string'],
						// 'value' => ['type' => 'string'],
						// ],
						// ],
						// ],
					),
				),
				'additionalItems' => true,
			),
			'fee_lines'                    => array(
				'type'            => 'array',
				'items'           => array(
					'type'       => 'object',
					'properties' => array(
						'id'         => array( 'type' => 'integer' ),
						'order_id'   => array( 'type' => 'integer' ),
						'name'       => array( 'type' => 'string' ),
						'tax_class'  => array( 'type' => 'string' ),
						'tax_status' => array( 'type' => 'string' ),
						'amount'     => array( 'type' => 'number' ),
						'total'      => array( 'type' => 'number' ),
						'total_tax'  => array(
							'type'       => 'object',
							'properties' => array(
								'amount'     => array( 'type' => 'number' ),
								'rate'       => array( 'type' => 'number' ),
								'percentage' => array( 'type' => 'number' ),
							),
						),
						'taxes'      => array(
							'type'       => 'object',
							'properties' => array(
								'total' => array(
									'type'            => 'array',
									'items'           => array( 'type' => 'number' ),
									'additionalItems' => true,
								),
							),
							'required'   => array( 'total' ),
						),
						// 'meta_data' => [
						// 'type' => 'array',
						// 'items' => [
						// 'type' => 'object',
						// 'properties' => [
						// 'id' => ['type' => 'integer'],
						// 'key' => ['type' => 'string'],
						// 'value' => ['type' => 'string'],
						// ],
						// ],
						// ],
					),
				),
				'additionalItems' => true,
			),
			'coupon_lines'                 => array(
				'type'            => 'array',
				'items'           => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'order_id'     => array( 'type' => 'integer' ),
						'name'         => array( 'type' => 'string' ),
						'code'         => array( 'type' => 'string' ),
						'discount'     => array( 'type' => 'number' ),
						'discount_tax' => array( 'type' => 'number' ),
						// 'meta_data' => [
						// 'type' => 'array',
						// 'items' => [
						// 'type' => 'object',
						// 'properties' => [
						// 'id' => ['type' => 'integer'],
						// 'key' => ['type' => 'string'],
						// 'value' => ['type' => 'string'],
						// ],
						// ],
						// ],
					),
				),
				'additionalItems' => true,
			),
		),
		'additionalProperties' => false,
	);

	/**
	 * Wraps a tax amount in a tax descriptor array.
	 *
	 * @param float $tax Tax amount.
	 * @param float $total Tax total import.
	 *
	 * @return array Tax descriptor.
	 */
	private static function decorate_tax( $tax, $total ) {
		try {
			$tax  = (float) $tax;
			$rate = $tax / $total;
			$rate = floor( $rate * 1000 ) / 1000;

			return array(
				'amount'     => $tax,
				'rate'       => $rate,
				'percentage' => $rate * 100,
			);
		} catch ( TypeError | DivisionByZeroError ) {
			return array(
				'amount'     => 0,
				'rate'       => 0,
				'percentage' => 0,
			);
		}
	}

	/**
	 * Integration initializer. Hooks the integration to woocommerce events.
	 */
	public function init() {
		add_action(
			'woocommerce_order_status_changed',
			static function ( $order_id, $old_status, $new_status ) {
				$is_bridged = get_post_meta(
					$order_id,
					self::ORDER_BRIDGED_CF,
					true
				);

				$trigger_submission = apply_filters(
					'forms_bridge_woo_trigger_submission',
					'1' !== $is_bridged && 'completed' === $new_status,
					$order_id,
					$new_status,
					$old_status,
					$is_bridged
				);

				if ( $trigger_submission ) {
					self::$order_id = $order_id;

					add_action(
						'forms_bridge_after_submission',
						function () {
							update_post_meta(
								self::$order_id,
								self::ORDER_BRIDGED_CF,
								'1'
							);
						},
						90
					);

					Forms_Bridge::do_submission();
				}
			},
			10,
			3
		);
	}

	/**
	 * Retrives the checkout form data if an order is being processed.
	 *
	 * @return array|null
	 */
	public function form() {
		if ( empty( self::$order_id ) ) {
			return;
		}

		return $this->get_form_by_id( 1 );
	}

	/**
	 * Retrives checkout form data by ID.
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array|null Form data if ID is 1, null otherwise.
	 */
	public function get_form_by_id( $form_id ) {
		if ( 1 !== +$form_id ) {
			return null;
		}

		WC()->session  = new WC_Session_Handler();
		WC()->customer = new WC_Customer();

		return apply_filters(
			'forms_bridge_form_data',
			array(
				'_id'     => 'woo:1',
				'id'      => 1,
				'title'   => __( 'Woo Checkout', 'forms-bridge' ),
				'bridges' => FBAPI::get_form_bridges( 1, 'woo' ),
				'fields'  => $this->serialize_form( null ),
			),
			WC()->checkout,
			'woo'
		);
	}

	/**
	 * Retrives available forms' data.
	 *
	 * @return array Collection of form data array representations.
	 */
	public function forms() {
		return array( $this->get_form_by_id( 1 ) );
	}

	/**
	 * Skips form creation and return a success result.
	 *
	 * @param array $data Form template data, ignored.
	 *
	 * @return int 1, the checkout form internal ID.
	 */
	public function create_form( $data ) {
		return 1;
	}

	/**
	 * Skips form removal and return a success result.
	 *
	 * @param integer $form_id Form ID, ignored.
	 *
	 * @return boolean
	 */
	public function remove_form( $form_id ) {
		return true;
	}

	/**
	 * Retrives the current order ID.
	 *
	 * @return string|null
	 */
	public function submission_id() {
		if ( self::$order_id ) {
			return (string) self::$order_id;
		}
	}

	/**
	 * Retrives the current order data.
	 *
	 * @param boolean $raw Control if the order is serialized before exit.
	 *
	 * @return array|null
	 */
	public function submission( $raw ) {
		if ( empty( self::$order_id ) ) {
			return;
		}

		return $this->serialize_order( self::$order_id );
	}

	/**
	 * Return an empty array as checkout form does not supports file uploads.
	 *
	 * @return array
	 */
	public function uploads() {
		return array();
	}

	/**
	 * Alias to the serialize_order_fields method.
	 *
	 * @param mixed $form Ignored argument.
	 *
	 * @return array The order fields serialized as array of data.
	 */
	public function serialize_form( $form ) {
		return $this->serialize_order_fields();
	}

	/**
	 * Serialize the order fields.
	 *
	 * @return array Order fields as form data.
	 */
	private function serialize_order_fields() {
		$checkout_fields = WC()->checkout->checkout_fields;

		$fields = array();
		foreach (
			self::$order_data_schema['properties']
			as $name => $field_schema
		) {
			$fields[] = self::decorate_order_field( $name, $field_schema );
		}

		foreach ( array_keys( $checkout_fields['billing'] ) as $name ) {
			$name = str_replace( 'billing_', '', $name );
			if ( isset( self::$order_data_schema['billing'][ $name ] ) ) {
				continue;
			}

			$index = array_search( 'billing', array_column( $fields, 'name' ), true );

			$billing_field                                  = &$fields[ $index ];
			$billing_field['schema']['properties'][ $name ] = array(
				'type' => 'text',
			);
		}

		foreach ( array_keys( $checkout_fields['shipping'] ) as $name ) {
			$name = str_replace( 'shipping_', '', $name );
			if ( isset( self::$order_data_schema['shipping'][ $name ] ) ) {
				continue;
			}

			$index = array_search( 'shipping', array_column( $fields, 'name' ), true );

			$fields[ $index ]['schema']['properties'][ $name ] = array(
				'type' => 'text',
			);
		}

		return $fields;
	}

	/**
	 * Decorates order fields as form data fields.
	 *
	 * @param string $name Field name.
	 * @param array  $schema Field schema.
	 *
	 * @return array Field data.
	 */
	private function decorate_order_field( $name, $schema ) {
		switch ( $schema['type'] ) {
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
				$field_type = 'select';
				break;
			default:
				$field_type = $schema['type'];
		}

		return array(
			'id'          => null,
			'name'        => $name,
			'label'       => $name,
			'type'        => $field_type,
			'required'    => true,
			'is_file'     => false,
			'is_multi'    => 'array' === $schema['type'],
			'conditional' => false,
			'schema'      => $schema,
		);
	}

	/**
	 * Alias to the serialize_order method.
	 *
	 * @param array $submission Ignored argument.
	 * @param array $form_data Ignored argument.
	 *
	 * @return array|null
	 */
	public function serialize_submission( $submission, $form_data ) {
		return $this->serialize_order( self::$order_id );
	}

	/**
	 * Serialize the current WC_Order as a payload array.
	 *
	 * @param integer $order_id ID of the order to serialize.
	 *
	 * @return array
	 */
	public function serialize_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return;
		}

		$checkout = WC()->checkout;

		$data = $order->get_data();
		unset( $data['meta_data'] );

		$checkout_fields = $checkout->checkout_fields;
		foreach ( array_keys( $checkout_fields['billing'] ) as $name ) {
			$unprefixed = str_replace( 'billing_', '', $name );
			if ( ! isset( $data['billing'][ $unprefixed ] ) ) {
				$data['billing'][ $unprefixed ] = $checkout->get_value( $name );
			}
		}

		foreach ( array_keys( $checkout_fields['shipping'] ) as $name ) {
			$unprefixed = str_replace( 'shipping_', '', $name );
			if ( ! isset( $data['shipping'][ $unprefixed ] ) ) {
				$data['shipping'][ $unprefixed ] = $checkout->get_value( $name );
			}
		}

		$tax_lines = array();
		foreach ( $data['tax_lines'] as $tax_line ) {
			$line_data = $tax_line->get_data();
			unset( $line_data['meta_data'] );
			$tax_lines[] = $line_data;
		}

		$data['tax_lines'] = $tax_lines;

		$line_items = array();
		foreach ( $data['line_items'] as $line_item ) {
			$item_data = $line_item->get_data();
			unset( $item_data['meta_data'] );

			$product              = $line_item->get_product();
			$item_data['product'] = array(
				'id'             => $product->get_id(),
				'parent_id'      => $product->get_parent_id(),
				'slug'           => $product->get_slug(),
				'sku'            => $product->get_sku(),
				'name'           => $product->get_name(),
				'price'          => $product->get_price(),
				'sale_price'     => $product->get_sale_price(),
				'regular_price'  => $product->get_regular_price(),
				'stock_quantity' => $product->get_stock_quantity(),
				'stock_status'   => $product->get_stock_status(),
			);

			$item_data['total_tax'] = self::decorate_tax(
				$line_item['total_tax'],
				$line_item['total']
			);

			$item_data['subtotal_tax'] = self::decorate_tax(
				$line_item['subtotal_tax'],
				$line_item['subtotal']
			);

			$line_items[] = $item_data;
		}

		$data['line_items'] = $line_items;

		$shipping_lines = array();
		foreach ( $data['shipping_lines'] as $shipping_line ) {
			$line_data = $shipping_line->get_data();
			unset( $line_data['meta_data'] );

			$line_data['total_tax'] = self::decorate_tax(
				$line_data['total_tax'],
				$line_data['total']
			);

			$shipping_lines[] = $line_data;
		}

		$data['shipping_lines'] = $shipping_lines;

		$coupon_lines = array();
		foreach ( $data['coupon_lines'] ?? array() as $coupon_line ) {
			$line_data = $coupon_line->get_data();
			unset( $line_data['meta_data'] );

			$line_data['discount_tax'] = self::decorate_tax(
				$line_data['discount_tax'],
				$line_data['discount']
			);

			$coupon_lines[] = $line_data;
		}

		$data['coupon_lines'] = $coupon_lines;

		$fee_lines = array();
		foreach ( $data['fee_lines'] ?? array() as $fee_line ) {
			$line_data = $fee_line->get_data();
			unset( $line_data['meta_data'] );

			$line_data['total_tax'] = self::decorate_tax(
				$line_data['total_tax'],
				$line_data['total']
			);

			$fee_lines[] = $line_data;
		}

		$data['fee_lines'] = $fee_lines;

		$data['discount_tax'] = self::decorate_tax(
			$data['discount_tax'],
			$data['discount_total']
		);

		$data['shipping_tax'] = self::decorate_tax(
			$data['shipping_tax'],
			$data['shipping_total']
		);

		$data['total_tax'] = self::decorate_tax(
			$data['total_tax'],
			$data['total']
		);

		$cart_total = 0;
		foreach ( $data['line_items'] as $line_data ) {
			$cart_total += $line_data['total'];
		}

		foreach ( $data['fee_lines'] as $line_data ) {
			$cart_total += $line_data['total'];
		}

		$data['cart_total'] = $cart_total;
		$data['cart_tax']   = self::decorate_tax(
			$data['cart_tax'],
			$data['cart_total']
		);

		return rest_sanitize_value_from_schema( $data, self::$order_data_schema );
	}
}

Woo_Integration::setup();
