<?php
/**
 * Class GravityFormsTest
 *
 * @package forms-bridge-tests
 */

require_once 'class-base-integration-test.php';

/**
 * GravityForms integration test case.
 */
class GravityFormsTest extends BaseIntegrationTest {
	public const NAME = 'gf';

	/**
	 * Fetch forms from the database.
	 *
	 * @return GFForm[]
	 */
	protected static function get_forms() {
		return GFAPI::get_forms();
	}

	/**
	 * Registers a new form on the database.
	 *
	 * @param object $config Form config.
	 *
	 * @return int Form ID.
	 */
	protected static function add_form( $config ) {
		return GFAPI::add_form( $config );
	}

	/**
	 * Delete a form from de database by ID.
	 *
	 * @param object $form Form data.
	 *
	 * @return bool 1 if OK, 0 if KO.
	 */
	protected static function delete_form( $form ) {
		return GFAPI::delete_form( $form['id'] );
	}

	public function test_signup_form_serialization() {
		$form = self::get_form( 'Onboarding el Prat de Llobregat' );

		$form_data = $this->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 18, count( $fields ) );

		$field = $fields[0];
		$this->assertEquals( 2, count( $field['options'] ) );
		$this->assertField( $field, 'select', array( '_type' => 'radio' ) );

		$field = $fields[4];
		$this->assertField( $field, 'text', array( 'conditional' => true ) );

		$field = $fields[7];
		$this->assertField(
			$field,
			'date',
			array(
				'conditional' => true,
				'format'      => 'yyyy-mm-dd',
			)
		);

		$field = $fields[15];
		$this->assertEquals( 1, count( $field['options'] ) );
		$this->assertEquals( 1, count( $field['inputs'] ) );
		$this->assertField(
			$field,
			'checkbox',
			array(
				'required' => false,
				'schema'   => 'boolean',
				'_type'    => 'consent',
			)
		);

		$field = $fields[16];
		$this->assertEquals( 1, count( $field['options'] ) );
		$this->assertEquals( 1, count( $field['inputs'] ) );
		$this->assertField(
			$field,
			'checkbox',
			array(
				'schema'      => 'boolean',
				'_type'       => 'consent',
				'conditional' => true,
			)
		);
	}

	public function test_serialize_signup_submission() {
		$form = self::get_form( 'Onboarding el Prat de Llobregat' );

		$form_data = $this->serialize_form( $form );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( 'signup-submission' === $name ) {
				$submission = $object;
				break;
			}
		}

		if ( ! isset( $submission ) ) {
			throw new Exception( 'Signup submission not found' );
		}

		$payload = $this->serialize_submission( $submission, $form_data );

		$this->assertSame( '0', $payload['Ets soci o usuari de Som Mobilitat?'] );
		$this->assertSame( 'female', $payload['gender'] );
		$this->assertSame( '1990-01-01', $payload['birthdate'] );
		$this->assertSame( '2026-12-10', $payload['driving_license_date'] );
		$this->assertTrue( $payload['newsletter'] );
		$this->assertTrue( $payload['no_member_consent'] );
	}

	public function test_subscription_form_serialization() {
		$form = self::get_form( 'Subscription Request' );

		$form_data = $this->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 16, count( $fields ) );

		$field = $fields[0];
		$this->assertField(
			$field,
			'text',
			array(
				'_type'    => 'hidden',
				'required' => false,
			)
		);

		$field = $fields[2];
		$this->assertEquals( 2, count( $field['inputs'] ) );
		$this->assertField( $field, 'text', array( '_type' => 'name' ) );

		$field = $fields[4];
		$this->assertField( $field, 'text', array( '_type' => 'phone' ) );

		$field = $fields[5];
		$this->assertField( $field, 'email' );

		$field = $fields[9];
		$this->assertField(
			$field,
			'number',
			array(
				'_type'  => 'quantity',
				'schema' => 'number',
			)
		);

		$field = $fields[10];
		$this->assertField( $field, 'select' );

		$field = $fields[13];
		$this->assertField(
			$field,
			'file',
			array(
				'_type'       => 'fileupload',
				'is_file'     => true,
				'conditional' => true,
			)
		);

		$field = $fields[14];
		$this->assertField( $field, 'textarea', array( 'required' => false ) );

		$field = $fields[15];
		$this->assertEquals( 1, count( $field['inputs'] ) );
		$this->assertField(
			$field,
			'checkbox',
			array(
				'_type'  => 'consent',
				'schema' => 'boolean',
			)
		);
	}

	public function test_serialize_sr_submission() {
		$form = self::get_form( 'Subscription Request' );

		$form_data = $this->serialize_form( $form );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( 'subscription-request-submission' === $name ) {
				$submission = $object;
				break;
			}
		}

		if ( ! isset( $submission ) ) {
			throw new Exception( 'Subscription Request submission not found' );
		}

		$payload = $this->serialize_submission( $submission, $form_data );

		$this->assertSame( 'EUSEBIO SALGADO', $payload['Nom i cognoms'] );
		$this->assertSame( 'website', $payload['source'] );
		$this->assertTrue( $payload['consent'] );
		$this->assertSame( '1', $payload['add_collect_account'][0] );
	}
}
