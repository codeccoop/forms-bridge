<?php
/**
 * Class GravityFormsTest
 *
 * @package forms-bridge-tests
 */

use FORMS_BRIDGE\Integration;

/**
 * GravityForms integration test case.
 */
class GravityFormsTest extends WP_UnitTestCase {
	private static function get_form( $title ) {
		$forms = GFAPI::get_forms();

		foreach ( $forms as $form ) {
			if ( $form['title'] === $title ) {
				return $form;
			}
		}

		throw new Exception( "Form {$title} not found" );
	}

	private function assertField( $field, $type, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'        => $type,
				'schema'      => 'string',
				'required'    => true,
				'is_file'     => false,
				'is_multi'    => false,
				'conditional' => false,
			)
		);

		$this->assertSame( $args['type'], $field['type'] );

		if ( isset( $args['gf'] ) ) {
			$this->assertSame( $args['gf'], $field['_type'] );
		} else {
			$this->assertSame( $field['type'], $field['_type'] );
		}

		if ( $args['schema'] ) {
			$this->assertSame( $args['schema'], $field['schema']['type'] );
		} else {
			$this->assertNull( $args['schema'] );
		}

		$flags = array( 'required', 'is_file', 'is_multi', 'conditional' );
		foreach ( $flags as $flag ) {
			if ( $args[ $flag ] ) {
				$this->assertTrue( $field[ $flag ] );
			} else {
				$this->assertFalse( $field[ $flag ] );
			}
		}

		if ( isset( $args['format'] ) ) {
			$this->assertSame( $args['format'], $field['format'] );
		}
	}

	public static function store() {
		$dir = dirname( __DIR__, 1 ) . '/data/gf';

		$store = array();
		foreach ( array_diff( scandir( $dir ), array( '..', '.' ) ) as $filename ) {
			$name           = explode( '.', $filename )[0];
			$filepath       = $dir . '/' . $filename;
			$store[ $name ] = unserialize( file_get_contents( $filepath ) );
		}

		return $store;
	}

	public static function set_up_before_class() {
		Integration::update_registry( array( 'gf' => true ) );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( ! str_ends_with( $name, '-form' ) ) {
				continue;
			}

			$form_id = GFAPI::add_form( $object );

			if ( ! $form_id ) {
				throw new Exception( 'Unable to create GF Form' );
			}
		}
	}

	public static function tear_down_after_class() {
		$forms = GFAPI::get_forms();

		foreach ( $forms as $form ) {
			GFAPI::delete_form( $form['id'] );
		}

		Integration::update_registry( array( 'gf' => false ) );
	}

	public function test_signup_form_serialization() {
		$form = self::get_form( 'Onboarding el Prat de Llobregat' );

		$integration = Integration::integration( 'gf' );
		$form_data   = $integration->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 18, count( $fields ) );

		$field = $fields[0];
		$this->assertEquals( 2, count( $field['options'] ) );
		$this->assertField( $field, 'select', array( 'gf' => 'radio' ) );

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
				'gf'       => 'consent',
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
				'gf'          => 'consent',
				'conditional' => true,
			)
		);
	}

	public function test_serialize_signup_submission() {
		$form = self::get_form( 'Onboarding el Prat de Llobregat' );

		$integration = Integration::integration( 'gf' );

		$form_data = $integration->serialize_form( $form );

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

		$payload = $integration->serialize_submission( $submission, $form_data );

		$this->assertSame( '0', $payload['Ets soci o usuari de Som Mobilitat?'] );
		$this->assertSame( 'female', $payload['gender'] );
		$this->assertSame( '1990-01-01', $payload['birthdate'] );
		$this->assertSame( '2026-12-10', $payload['driving_license_date'] );
		$this->assertTrue( $payload['newsletter'] );
		$this->assertTrue( $payload['no_member_consent'] );
	}

	public function test_subscription_form_serialization() {
		$form = self::get_form( 'Subscription Request' );

		$integration = Integration::integration( 'gf' );

		$form_data = $integration->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 16, count( $fields ) );

		$field = $fields[0];
		$this->assertField(
			$field,
			'text',
			array(
				'gf'       => 'hidden',
				'required' => false,
			)
		);

		$field = $fields[2];
		$this->assertEquals( 2, count( $field['inputs'] ) );
		$this->assertField( $field, 'text', array( 'gf' => 'name' ) );

		$field = $fields[4];
		$this->assertField( $field, 'text', array( 'gf' => 'phone' ) );

		$field = $fields[5];
		$this->assertField( $field, 'email' );

		$field = $fields[9];
		$this->assertField(
			$field,
			'number',
			array(
				'gf'     => 'quantity',
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
				'gf'          => 'fileupload',
				'schema'      => null,
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
				'gf'     => 'consent',
				'schema' => 'boolean',
			)
		);
	}

	public function test_serialize_sr_submission() {
		$form = self::get_form( 'Subscription Request' );

		$integration = Integration::integration( 'gf' );

		$form_data = $integration->serialize_form( $form );

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

		$payload = $integration->serialize_submission( $submission, $form_data );

		$this->assertSame( 'EUSEBIO SALGADO', $payload['Nom i cognoms'] );
		$this->assertSame( 'website', $payload['source'] );
		$this->assertTrue( $payload['consent'] );
		$this->assertSame( '1', $payload['add_collect_account'][0] );
	}
}
