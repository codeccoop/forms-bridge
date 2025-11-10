<?php
/**
 * Class NinjaFormsTest
 *
 * @package forms-bridge-tests
 */

use FORMS_BRIDGE\Integration;

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
}
