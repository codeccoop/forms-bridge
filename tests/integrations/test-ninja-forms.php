<?php
/**
 * Class NinjaFormsTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Integration;

require_once 'class-base-integration-test.php';

/**
 * Ninja Forms integration test case.
 */
class NinjaFormsTest extends BaseIntegrationTest {
	public const NAME = 'ninja';

	protected static function get_forms() {
		$store = self::store();
		$forms = array();
		foreach ( $store as $key => $object ) {
			if ( str_ends_with( $key, '-form' ) ) {
				$forms[] = $object;
			}
		}

		return $forms;
	}

	protected static function add_form( $config ) {
		return 1;
	}

	protected static function delete_form( $form ) {
		return true;
	}

	public function serialize_form( $form ) {
		$integration = Integration::integration( 'ninja' );

		$fields = array();
		foreach ( $form['fields'] as $field ) {
			if (
				in_array(
					$field['type'],
					array( 'html', 'hr', 'confirm', 'recaptcha', 'spam', 'submit' ),
					true,
				)
			) {
				continue;
			}

			$fields[] = $integration->serialize_field_settings( 1, $field, $form['settings'] );
		}

		return array(
			'_id'     => 'ninja:1',
			'id'      => '1',
			'title'   => $form['settings']['title'],
			'bridges' => array(),
			'fields'  => array_values( $fields ),
		);
	}

	public function test_enquiry_form_serialization() {
		$form = self::get_form( 'Enquiry' );

		$form_data = self::serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 9, count( $fields ) );

		$field = $fields[0];
		$this->assertField(
			$field,
			'text',
			array(
				'basetype' => 'firstname',
				'required' => false,
			)
		);

		$field = $fields[1];
		$this->assertField(
			$field,
			'text',
			array(
				'basetype' => 'lastname',
				'required' => false,
			)
		);

		$field = $fields[2];
		$this->assertField( $field, 'email', array( 'required' => false ) );

		$field = $fields[3];
		$this->assertField(
			$field,
			'tel',
			array(
				'required' => false,
				'basetype' => 'phone',
			)
		);

		$field = $fields[4];
		$this->assertField(
			$field,
			'select',
			array(
				'required' => false,
				'basetype' => 'listradio',
				'opptions' => array(
					array(
						'label' => 'Choice 1',
						'value' => 'choice1',
					),
					array(
						'label' => 'Choice 2',
						'value' => 'choice2',
					),
					array(
						'label' => 'Choice 3',
						'value' => 'choice3',
					),
				),
			),
		);

		$field = $fields[5];
		$this->assertField(
			$field,
			'select',
			array(
				'required' => false,
				'basetype' => 'listselect',
				'options'  => 3,
			)
		);

		$field = $fields[6];
		$this->assertField( $field, 'textarea', array( 'required' => false ) );

		$field = $fields[7];
		$this->assertField(
			$field,
			'checkbox',
			array(
				'required' => false,
				'schema'   => 'boolean',
			)
		);
	}

	function test_enquiry_submission_serialization() {
		$form      = self::get_form( 'Enquiry' );
		$form_data = $this->serialize_form( $form );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( 'enquiry-submission' === $name ) {
				$submission = $object;
				break;
			}
		}

		if ( ! isset( $submission ) ) {
			throw new Exception( 'Enquiry submission not found' );
		}

		$l = count( $form_data['fields'] );
		for ( $i = 0; $i < $l; ++$i ) {
			$form_data['fields'][ $i ]['id'] = array_keys( $submission['fields'] )[ $i ];
		}

		$payload = $this->serialize_submission( $submission, $form_data );

		$this->assertSame( 'John', $payload['firstname_1523908368154'] );
		$this->assertSame( 'Doe', $payload['lastname_1523908369534'] );
		$this->assertSame( '600000000', $payload['phone_1523908588264'] );
		$this->assertSame( 'choice1', $payload['occupation_1523908435932'] );
		$this->assertSame( 'choice2', $payload['enquiry_type_1523908495090'] );
		$this->assertSame( 'Lorem ipsum dolor sit amer', $payload['details_1523908537286'] );
		$this->assertSame( 1, $payload['may_we_contact_you_1523908579864'] );
		$this->assertSame( 'afternoon', $payload['best_time_to_call_1523908689926'] );
	}

	public function test_quote_request_form_serialization() {
		$form      = self::get_form( 'Quote Request Form' );
		$form_data = $this->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 12, count( $fields ) );

		$field = $fields[2];
		$this->assertField(
			$field,
			'date',
			array(
				'required' => false,
				'format'   => 'dd/mm/yyyy',
			),
		);

		$field = $fields[8];
		$this->assertField(
			$field,
			'text',
			array(
				'basetype' => 'address',
				'required' => false,
			)
		);

		$field = $fields[9];
		$this->assertField(
			$field,
			'text',
			array(
				'basetype' => 'city',
				'required' => false,
			)
		);

		$field = $fields[10];
		$this->assertField(
			$field,
			'select',
			array(
				'basetype' => 'liststate',
				'required' => false,
			)
		);

		$field = $fields[11];
		$this->assertField(
			$field,
			'text',
			array(
				'required' => false,
				'basetype' => 'zip',
			)
		);
	}

	public function test_quote_request_submission_serialization() {
		$form      = self::get_form( 'Quote Request Form' );
		$form_data = $this->serialize_form( $form );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( 'quote-request-submission' === $name ) {
				$submission = $object;
				break;
			}
		}

		if ( ! isset( $submission ) ) {
			throw new Exception( 'Quote request submission not found' );
		}

		$i = 0;
		$j = 0;
		$l = count( $form_data['fields'] );
		$k = count( $submission['fields'] );
		while ( $i < $l && $j < $k ) {
			$field_id         = array_keys( $submission['fields'] )[ $j ];
			$submission_field = $submission['fields'][ $field_id ];

			if (
				in_array(
					$submission_field['type'],
					array( 'html', 'confirm', 'hr', 'recaptcha', 'spam', 'submit' ),
					true,
				)
			) {
				++$j;
				continue;
			}

			$form_data['fields'][ $i ]['id'] = $field_id;
			++$i;
			++$j;
		}

		$payload = $this->serialize_submission( $submission, $form_data );

		$this->assertSame( '13/11/2025', $payload['due_date'] );
		$this->assertSame( 'Carrer de les Camèlies', $payload['address'] );
		$this->assertSame( 'Barcelona', $payload['city'] );
		$this->assertSame( 'CA', $payload['liststate'] );
		$this->assertSame( '08001', $payload['zip'] );
	}

	public function test_questionnaire_form_serialization() {
		$form      = self::get_form( 'Questionnaire' );
		$form_data = $this->serialize_form( $form );

		$fields = $form_data['fields'];
		$this->assertEquals( 11, count( $fields ) );

		$field = $fields[6];
		$this->assertField(
			$field,
			'select',
			array(
				'required' => false,
				'basetype' => 'listmultiselect',
				'schema'   => 'array',
				'is_multi' => true,
				'options'  => 3,
			)
		);

		$field = $fields[7];
		$this->assertField(
			$field,
			'select',
			array(
				'required' => false,
				'basetype' => 'listcheckbox',
				'is_multi' => true,
				'schema'   => 'array',
				'options'  => 3,
			),
		);

		$field = $fields[10];
		$this->assertField(
			$field,
			'number',
			array(
				'required' => false,
				'basetype' => 'starrating',
				'schema'   => 'number',
			),
		);
	}

	public function test_questionnaire_submission_serialization() {
		$form      = self::get_form( 'Questionnaire' );
		$form_data = $this->serialize_form( $form );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( 'questionnaire-submission' === $name ) {
				$submission = $object;
				break;
			}
		}

		if ( ! isset( $submission ) ) {
			throw new Exception( 'Questionnaire submission not found' );
		}

		$i = 0;
		$j = 0;
		$l = count( $form_data['fields'] );
		$k = count( $submission['fields'] );
		while ( $i < $l && $j < $k ) {
			$field_id         = array_keys( $submission['fields'] )[ $j ];
			$submission_field = $submission['fields'][ $field_id ];

			if (
				in_array(
					$submission_field['type'],
					array( 'html', 'confirm', 'hr', 'recaptcha', 'spam', 'submit' ),
					true,
				)
			) {
				++$j;
				continue;
			}

			$form_data['fields'][ $i ]['id'] = $field_id;
			++$i;
			++$j;
		}

		$payload = $this->serialize_submission( $submission, $form_data );

		$this->assertSame( '18-30', $payload['age_1523910843594'] );
		$this->assertEqualSets(
			array( 'one', 'two' ),
			$payload['question_2_1762818391757']
		);
		$this->assertEqualSets(
			array( 'one', 'three' ),
			$payload['question_3_1762818351190'],
		);
		$this->assertEquals( 3, $payload['starrating_1762818417160'] );
	}
}
