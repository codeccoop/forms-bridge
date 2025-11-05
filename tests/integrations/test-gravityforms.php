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

	public function test_subscription_form_serialization() {
		$forms = GFAPI::get_forms();

		foreach ( $forms as $candidate ) {
			if ( 'Subscription Request' === $candidate['title'] ) {
				$form = $candidate;
				break;
			}
		}

		if ( ! isset( $form ) ) {
			throw new Exception( 'Subscription Request not found' );
		}

		$integration = Integration::integration( 'gf' );

		$form_data = $integration->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 16, count( $fields ) );

		$field = $fields[0];
		$this->assertSame( 'text', $field['type'] );
		$this->assertSame( 'string', $field['schema']['type'] );
		$this->assertSame( 'hidden', $field['_type'] );
		$this->assertFalse( $field['required'] );
		$this->assertFalse( $field['is_file'] );
		$this->assertFalse( $field['is_multi'] );
		$this->assertFalse( $field['conditional'] );

		$field = $fields[2];
		$this->assertSame( 'text', $field['type'] );
		$this->assertSame( 'string', $field['schema']['type'] );
		$this->assertSame( 'name', $field['_type'] );
		$this->assertTrue( $field['required'] );
		$this->assertFalse( $field['is_file'] );
		$this->assertFalse( $field['is_multi'] );
		$this->assertFalse( $field['conditional'] );
		$this->assertEquals( 2, count( $field['inputs'] ) );

		$field = $fields[4];
		$this->assertSame( 'text', $field['type'] );
		$this->assertSame( 'string', $field['schema']['type'] );
		$this->assertSame( 'phone', $field['_type'] );
		$this->assertTrue( $field['required'] );
		$this->assertFalse( $field['is_file'] );
		$this->assertFalse( $field['is_multi'] );
		$this->assertFalse( $field['conditional'] );

		$field = $fields[5];
		$this->assertSame( 'email', $field['type'] );
		$this->assertSame( 'string', $field['schema']['type'] );
		$this->assertSame( 'email', $field['_type'] );
		$this->assertTrue( $field['required'] );
		$this->assertFalse( $field['is_file'] );
		$this->assertFalse( $field['is_multi'] );
		$this->assertFalse( $field['conditional'] );

		$field = $fields[9];
		$this->assertSame( 'number', $field['type'] );
		$this->assertSame( 'number', $field['schema']['type'] );
		$this->assertSame( 'quantity', $field['_type'] );
		$this->assertTrue( $field['required'] );
		$this->assertFalse( $field['is_file'] );
		$this->assertFalse( $field['is_multi'] );
		$this->assertFalse( $field['conditional'] );

		$field = $fields[10];
		$this->assertSame( 'select', $field['type'] );
		$this->assertSame( 'string', $field['schema']['type'] );
		$this->assertSame( 'select', $field['_type'] );
		$this->assertTrue( $field['required'] );
		$this->assertFalse( $field['is_file'] );
		$this->assertFalse( $field['is_multi'] );
		$this->assertFalse( $field['conditional'] );
		$this->assertEquals( 2, count( $field['options'] ) );

		$field = $fields[13];
		$this->assertSame( 'file', $field['type'] );
		$this->assertNull( $field['schema'] );
		$this->assertSame( 'fileupload', $field['_type'] );
		$this->assertTrue( $field['required'] );
		$this->assertTrue( $field['is_file'] );
		$this->assertFalse( $field['is_multi'] );
		$this->assertTrue( $field['conditional'] );

		$field = $fields[14];
		$this->assertSame( 'textarea', $field['type'] );
		$this->assertSame( 'string', $field['schema']['type'] );
		$this->assertSame( 'textarea', $field['_type'] );
		$this->assertFalse( $field['required'] );
		$this->assertFalse( $field['is_file'] );
		$this->assertFalse( $field['is_multi'] );
		$this->assertFalse( $field['conditional'] );

		$field = $fields[15];
		$this->assertSame( 'switch', $field['type'] );
		$this->assertSame( 'boolean', $field['schema']['type'] );
		$this->assertSame( 'consent', $field['_type'] );
		$this->assertTrue( $field['required'] );
		$this->assertFalse( $field['is_file'] );
		$this->assertFalse( $field['is_multi'] );
		$this->assertFalse( $field['conditional'] );
		$this->assertEquals( 1, count( $field['inputs'] ) );
	}

	public function test_serialize_submission() {
		$forms = GFAPI::get_forms();

		foreach ( $forms as $candidate ) {
			if ( 'Subscription Request' === $candidate['title'] ) {
				$form = $candidate;
				break;
			}
		}

		if ( ! isset( $form ) ) {
			throw new Exception( 'Subscription Request not found' );
		}

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
