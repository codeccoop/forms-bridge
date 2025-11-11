<?php
/**
 * Class WPFormsTest
 *
 * @package forms-bridge-tests
 */

/**
 * WPForms integration test case.
 */
class WPFormsTest extends BaseIntegrationTest {
	public const NAME = 'wpforms';

	public static function set_up_before_class() {
		add_filter( 'wpforms_current_user_can', '__return_true', 90, 0 );
		parent::set_up_before_class();
	}

	public static function tear_down_after_class() {
		remove_filter( 'wpforms_create_form_args', '__return_true', 90, 0 );
		parent::tear_down_after_class();
	}

	protected static function get_forms() {
		return array_filter( (array) wpforms()->obj( 'form' )->get() );
	}

	protected static function add_form( $config ) {
		unset( $config['id'], $config['providers'], $config['payments'] );
		$config['meta'] = array( 'template' => 'forms-bridge' );

		add_filter(
			'wpforms_create_form_args',
			function ( $args ) use ( $config ) {
				$args['post_content'] = wpforms_encode( $config );
				return $args;
			},
			99,
			1
		);

		return wpforms()
			->obj( 'form' )
			->add(
				$config['settings']['form_title'],
				array(),
				array(
					'template'    => 'forms-bridge',
					'category'    => 'all',
					'subcategory' => 'all',
				)
			);
	}

	protected static function delete_form( $form ) {
		return wp_delete_post( $form->ID );
	}

	public function test_address_book_form_serialization() {
		$form = self::get_form( 'Address Book Form' );

		$form_data = $this->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 9, count( $fields ) );

		$field = $fields[0];
		$this->assertField( $field, 'text', array( 'basetype' => 'name' ) );

		$field = $fields[1];
		$this->assertField(
			$field,
			'file',
			array(
				'is_file'  => true,
				'required' => false,
				'basetype' => 'file-upload',
			)
		);

		$field = $fields[2];
		$this->assertField(
			$field,
			'text',
			array(
				'required' => false,
				'schema'   => 'object',
				'basetype' => 'address',
			)
		);

		$field = $fields[3];
		$this->assertField( $field, 'url', array( 'required' => false ) );

		$field = $fields[4];
		$this->assertField( $field, 'email', array( 'required' => false ) );

		$field = $fields[5];
		$this->assertField(
			$field,
			'tel',
			array(
				'required' => false,
				'basetype' => 'phone',
			)
		);

		$field = $fields[7];
		$this->assertField(
			$field,
			'date',
			array(
				'basetype' => 'date-time',
				'format'   => 'mm/dd/yyyy',
				'required' => false,
			),
		);

		$field = $fields[8];
		$this->assertField( $field, 'textarea', array( 'required' => false ) );
	}

	public function test_address_book_submission_serialization() {
		$form = self::get_form( 'Address Book Form' );

		$form_data = $this->serialize_form( $form );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( 'address-book-submission' === $name ) {
				$submission = $object;
				break;
			}
		}

		if ( ! isset( $submission ) ) {
			throw new Exception( 'Address book submission not found' );
		}

		$_POST['wpforms'] = $submission;

		$payload = $this->serialize_submission( $submission, $form_data );

		$this->assertSame( 'John Doe', $payload['Name'] );

		$this->assertEqualSets(
			array(
				'address1' => 'Carrer de les Camèlies',
				'address2' => '',
				'city'     => 'Barcelona',
				'state'    => 'Barcelona',
				'postal'   => '08001',
				'country'  => 'ES',
			),
			$payload['Address']
		);

		$this->assertSame( '+34600000000', $payload['Home Phone'] );
		$this->assertSame( '1/1/2001', $payload['Birthday'] );
	}

	public function test_donation_form_serialization() {
		$form = self::get_form( 'Donation Form' );

		$form_data = $this->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 4, count( $fields ) );

		$field = $fields[2];
		$this->assertField(
			$field,
			'text',
			array(
				'required' => false,
				'basetype' => 'payment-single',
			)
		);
	}

	public function test_donation_submission_serialization() {
		$form = self::get_form( 'Donation Form' );

		$form_data = $this->serialize_form( $form );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( 'donation-submission' === $name ) {
				$submission = $object;
				break;
			}
		}

		if ( ! isset( $submission ) ) {
			throw new Exception( 'Donation submission not found' );
		}

		$_POST['wpforms'] = $submission;

		$payload = $this->serialize_submission( $submission, $form_data );

		$this->assertSame( 'John Doe', $payload['Name'] );
		$this->assertSame( '$1,000.00', $payload['Donation Amount'] );
	}

	public function test_meeting_room_registration_form_serialization() {
		$form = self::get_form( 'Meeting Room Registration Form' );

		$form_data = $this->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 6, count( $fields ) );

		$field = $fields[3];
		$this->assertField(
			$field,
			'select',
			array(
				'options'  => array(
					array(
						'value' => 'Room A',
						'label' => 'Room A',
					),
					array(
						'value' => 'Room B',
						'label' => 'Room B',
					),
					array(
						'value' => 'Room C',
						'label' => 'Room C',
					),
				),
				'basetype' => 'radio',
			),
		);

		$field = $fields[4];
		$this->assertField(
			$field,
			'select',
			array(
				'options'  => 9,
				'schema'   => 'array',
				'is_multi' => true,
				'basetype' => 'checkbox',
			),
		);
	}

	public function test_meeting_room_registration_submission_serialization() {
		$form      = self::get_form( 'Meeting Room Registration Form' );
		$form_data = $this->serialize_form( $form );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( 'meeting-room-registration-submission' === $name ) {
				$submission = $object;
				break;
			}
		}

		if ( ! isset( $submission ) ) {
			throw new Exception( 'Meeting room reservation submission not found' );
		}

		$_POST['wpforms'] = $submission;

		$payload = $this->serialize_submission( $submission, $form_data );

		$this->assertSame( 'John Doe', $payload['Name'] );
		$this->assertSame( 'Room A', $payload['Which room would you like to reserve?'] );
		$this->assertEqualSets(
			array( '8:00 - 9:00am', '9:00 - 10:00am', '3:00 - 4:00pm' ),
			$payload['Which time blocks would you like to reserve?']
		);
	}
}
