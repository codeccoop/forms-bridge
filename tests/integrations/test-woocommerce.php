<?php
/**
 * Class WooCommerceTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Integration;

require_once 'class-base-integration-test.php';

/**
 * WooCommerce integration test case.
 */
class WooCommerceTest extends BaseIntegrationTest {
	public const NAME = 'woo';

	protected static function get_forms() {
		$integration   = Integration::integration( self::NAME );
		$checkout_form = $integration->get_form_by_id( 1 );
		return array( $checkout_form );
	}

	protected static function add_form( $config ) {
		return 1;
	}

	protected static function delete_form( $form ) {
		return true;
	}

	public function test_checkout_form_serialization() {
		$form_data = self::get_form( 'Woo Checkout' );

		$fields = $form_data['fields'];
		$this->assertEquals( 41, count( $fields ) );

		$field = $fields[0];
		$this->assertSame( 'id', $field['name'] );
		$this->assertField( $field, 'number', array( 'schema' => 'integer' ) );

		$field = $fields[1];
		$this->assertSame( 'parent_id', $field['name'] );
		$this->assertField( $field, 'number', array( 'schema' => 'integer' ) );

		$field = $fields[2];
		$this->assertSame( 'status', $field['name'] );
		$this->assertField( $field, 'text' );
	}

	public function test_checkout_submission_serialization() {
		$form_data = self::get_form( 'Woo Checkout' );

		$product = new WC_Product_Simple();
		$product->set_name( 'Foo' );
		$product->set_regular_price( '29.99' );
		$product->set_description( 'This is a sample product description.' );
		$product->set_short_description( 'A short description for the sample product.' );
		$product_id = $product->save();

		$order = wc_create_order();
		$order->add_product( $product, 2 );

		$order->set_address(
			array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'email'      => 'johndoe@example.coop',
				'phone'      => '600000000',
				'address_1'  => 'Carrer de les Camèlies',
				'city'       => 'Barcelona',
				'postcode'   => '08001',
				'country'    => 'ES',
			),
			'billing',
		);

		$order->set_payment_method( 'cod' );

		$order_id = $order->save();
		if ( empty( $order_id ) ) {
			throw new Exception( 'Checkout submission not found' );
		}

		$integration = Integration::integration( self::NAME );
		$payload     = $integration->serialize_order( $order_id );

		$this->assertSame( 'pending', $payload['status'] );
		$this->assertSame( 'John', $payload['billing']['first_name'] );
		$this->assertSame( 'Doe', $payload['billing']['last_name'] );
		$this->assertSame( 'Carrer de les Camèlies', $payload['billing']['address_1'] );
		$this->assertSame( 'Barcelona', $payload['billing']['city'] );
		$this->assertSame( 'johndoe@example.coop', $payload['billing']['email'] );
		$this->assertSame( '600000000', $payload['billing']['phone'] );
		$this->assertSame( 'cod', $payload['payment_method'] );
		$this->assertEquals( $product_id, $payload['line_items'][0]['product_id'] );
		$this->assertEquals( 2, $payload['line_items'][0]['quantity'] );
		$this->assertEquals( 59.98, $payload['line_items'][0]['subtotal'] );
		$this->assertEquals( 59.98, $payload['line_items'][0]['total'] );
	}
}
