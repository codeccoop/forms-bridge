<?php
/**
 * Class ConditionalMappersTest
 *
 * @package forms-bridge-tests
 */

use FORMS_BRIDGE\Form_Bridge;

class ConditionalMappersTest extends WP_UnitTestCase {
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
		return include __DIR__ . '/data/templates/shipping-form.php';
	}

	private function bridge() {
		return new Form_Bridge(
			array(
				'name'      => 'bridge-tests',
				'backend'   => 'backend',
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
	}

	public function test_prepare_conditional_mappers() {
		$bridge = $this->bridge();

		$form_data = $this->form_data();
		$bridge->prepare_mappers( $form_data );

		$mutation = $bridge->mutations[0];

		$this->assertEquals( '?street', $mutation[0]['from'] );
		$this->assertEquals( '?zip', $mutation[1]['from'] );
		$this->assertEquals( '?city', $mutation[2]['from'] );

		return $bridge;
	}

	public function test_apply_conditional_mappers() {
		$bridge = $this->bridge();

		$form_data = $this->form_data();
		$bridge->prepare_mappers( $form_data );

		$payload = $this->payload();

		$result = $bridge->apply_mutation( $payload );

		$this->assertTrue( isset( $result['shipping']['street'] ) );
		$this->assertTrue( isset( $result['shipping']['zip'] ) );
		$this->assertTrue( isset( $result['shipping']['city'] ) );

		unset( $payload['street'] );
		unset( $payload['zip'] );
		unset( $payload['city'] );

		$result = $bridge->apply_mutation( $payload );

		$this->assertTrue( ! isset( $result['shipping'] ) );
	}
}
