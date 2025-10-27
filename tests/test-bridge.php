<?php
/**
 * Class BridgeTest
 *
 * @package forms-bridge-tests
 */

use FORMS_BRIDGE\Form_Bridge;

class BridgeTest extends WP_UnitTestCase {
	private function payload() {
		return array(
			'name'         => 'John Doe',
			'email'        => 'jondoe@email.me',
			'gender'       => 'male',
			'age'          => 54,
			'subscription' => true,
			'street'       => 'Elm street',
			'zip'          => '00000',
			'city'         => 'Testburg',
		);
	}

	private function form_data() {
		return include './data/shipping-forms.php';
	}

	private function bridge() {
		return new Form_Bridge(
			array(
				'name'    => 'bridge-tests',
				'backend' => 'backend',
			)
		);
	}

	public function test_prepare_mappers() {
		$payload = $this->payload();
		$bridge  = $this->bridge()->patch(
			array(
				'mutations' => array(
					array(
						array(
							'from' => 'street',
							'to'   => 'shipping.street',
							'cast' => 'string',
						),
						array(
							'from' => 'zip',
							'to'   => 'shipping.zip',
							'cast' => 'string',
						),
						array(
							'from' => 'city',
							'to'   => 'shipping.city',
							'cast' => 'string',
						),
					),
				),
			)
		);

		$form_data = include __DIR__ . '/data/shipping-forms.php';
		$bridge->prepare_mappers( $form_data );

		$mutation = $bridge->mutations[0];

		$this->assertEquals( '?street', $mutation[0]['from'] );
		$this->assertEquals( '?zip', $mutation[1]['from'] );
		$this->assertEquals( '?city', $mutation[2]['from'] );

		$result = $bridge->apply_mutation( $payload, $mutation );

		$this->assertTrue( isset( $result['shipping']['street'] ) );
		$this->assertTrue( isset( $result['shipping']['zip'] ) );
		$this->assertTrue( isset( $result['shipping']['city'] ) );

		unset( $payload['street'] );
		unset( $payload['zip'] );
		unset( $payload['city'] );

		$result = $bridge->apply_mutation( $payload, $mutation );

		$this->assertTrue( ! isset( $result['shipping'] ) );
	}
}
