<?php
/**
 * Class GF_Integration
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE\GF;

use Error;
use FBAPI;
use TypeError;
use FORMS_BRIDGE\Forms_Bridge;
use FORMS_BRIDGE\Integration as BaseIntegration;
use GFAPI;
use GFCommon;
use GFFormDisplay;
use GFFormsModel;

// phpcs:disable WordPress.NamingConventions.ValidVariableName

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * GravityForms integration.
 */
class GF_Integration extends BaseIntegration {
	/**
	 * Handles integration name.
	 *
	 * @var string
	 */
	const NAME = 'gf';

	/**
	 * Handles integration title.
	 *
	 * @var string
	 */
	const TITLE = 'Gravity Forms';

	/**
	 * Binds after submission hook to the do_submission routine.
	 */
	protected function init() {
		add_action(
			'gform_after_submission',
			function () {
				Forms_Bridge::do_submission();
			}
		);
	}

	/**
	 * Retrives the current form's data.
	 *
	 * @return array|null
	 */
	public function form() {
		$form_id = null;
		if ( isset( $_POST['gform_submit'] ) ) {
			require_once GFCommon::get_base_path() . '/form_display.php';
			$form_id = GFFormDisplay::is_submit_form_id_valid();

			if ( ! $form_id && wp_doing_ajax() ) {
				if (
					isset( $_POST['gform_submission_method'] ) &&
					GFFormDisplay::SUBMISSION_METHOD_AJAX === $_POST['gform_submission_method']
				) {
					$form_id = absint( $_POST['gform_submit'] );
				}
			}
		}

		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) || ! $form['is_active'] || $form['is_trash'] ) {
			return null;
		}

		if ( is_wp_error( $form ) ) {
			return null;
		}

		return $this->serialize_form( $form );
	}

	/**
	 * Retrives a form's data by ID.
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array
	 */
	public function get_form_by_id( $form_id ) {
		$form = GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return null;
		}

		return $this->serialize_form( $form );
	}

	/**
	 * Retrives available forms' data.
	 *
	 * @return array Collection of form data array representations.
	 */
	public function forms() {
		$forms = GFAPI::get_forms();
		return array_map(
			function ( $form ) {
				return $this->serialize_form( $form );
			},
			array_filter(
				$forms,
				function ( $form ) {
					return $form['is_active'] && ! $form['is_trash'];
				}
			)
		);
	}

	/**
	 * Creates a form from a given template fields.
	 *
	 * @param array $data Form template data.
	 *
	 * @return int|null ID of the new form.
	 */
	public function create_form( $data ) {
		if ( empty( $data['title'] ) || empty( $data['fields'] ) ) {
			return;
		}

		$data = array_merge(
			$data,
			array(
				'id'                     => 1,
				'fields'                 => $this->prepare_fields( $data['fields'] ),
				'labelPlacement'         => 'top_label',
				'useCurrentUserAsAuthor' => '1',
				'postAuthor'             => '1',
				'postCategory'           => '1',
				'postStatus'             => 'publish',
				'button'                 => array(
					'type'             => 'text',
					'text'             => esc_html__( 'Submit', 'forms-bridge' ),
					'imageUrl'         => '',
					'conditionalLogic' => null,
				),
				'version'                => '2.7',
			)
		);

		$form_id = GFAPI::add_form( $data );

		if ( is_wp_error( $form_id ) ) {
			return;
		}

		return $form_id;
	}

	/**
	 * Removes a form by ID.
	 *
	 * @param integer $form_id Form ID.
	 *
	 * @return boolean Removal result.
	 */
	public function remove_form( $form_id ) {
		return GFFormsModel::delete_form( $form_id );
	}

	/**
	 * Retrives the current submission ID.
	 *
	 * @return string|null
	 */
	public function submission_id() {
		$submission = $this->submission( true );
		if ( $submission ) {
			return (string) $submission['id'];
		}
	}

	/**
	 * Retrives the current submission data.
	 *
	 * @param boolean $raw Control if the submission is serialized before exit.
	 *
	 * @return array|null
	 */
	public function submission( $raw = false ) {
		$form_data = $this->form();
		if ( ! $form_data ) {
			return null;
		}

		$submission = GFFormsModel::get_current_lead(
			GFAPI::get_form( $form_data['id'] )
		);

		if ( ! $submission ) {
			return;
		} elseif ( $raw ) {
			return $submission;
		}

		$form = $this->form();
		return $this->serialize_submission( $submission, $form );
	}

	/**
	 * Retrives the current submission uploaded files.
	 *
	 * @return array|null Collection of uploaded files.
	 */
	public function uploads() {
		$form_data = $this->form();
		if ( ! $form_data ) {
			return null;
		}

		$submission = GFFormsModel::get_current_lead(
			GFAPI::get_form( $form_data['id'] )
		);

		if ( ! $submission ) {
			return null;
		}

		$form = $this->form();
		return $this->submission_uploads( $submission, $form );
	}

	/**
	 * Serializes gf form's data.
	 *
	 * @param array $form GF form data.
	 *
	 * @return array
	 */
	public function serialize_form( $form ) {
		$form_id = (int) $form['id'];

		$fields = array();
		foreach ( $form['fields'] as $field ) {
			$field = $this->serialize_field( $field );

			if ( $field ) {
				$field  = wp_is_numeric_array( $field ) ? $field : array( $field );
				$fields = array_merge( $fields, $field );
			}
		}

		return apply_filters(
			'forms_bridge_form_data',
			array(
				'_id'     => 'gf:' . $form_id,
				'id'      => $form_id,
				'title'   => $form['title'],
				'bridges' => FBAPI::get_form_bridges( $form_id, 'gf' ),
				'fields'  => $fields,
			),
			$form,
			'gf'
		);
	}

	/**
	 * Serializes a GFField as array data.
	 *
	 * @param GFField $field Field object instance.
	 *
	 * @return array|null
	 */
	private function serialize_field( $field ) {
		if (
			in_array(
				$field->type,
				array(
					'page',
					'section',
					'html',
					'submit',
					'captcha',
				),
				true
			)
		) {
			return null;
		}

		if ( strstr( $field->type, 'post_' ) ) {
			return null;
		}

		$label = $field->adminLabel ?: $field->label;
		$name  = $field->inputName ?: $label;

		$allowsPrepopulate = $field->allowsPrepopulate ?? false;

		$choices = $field->choices ?: array();
		$options = array();
		foreach ( $choices as $choice ) {
			$options[] = array(
				'value' => $choice['value'],
				'label' => $choice['label'] ?? $choice['text'] ?? '',
			);
		}

		try {
			$inputs       = array();
			$entry_inputs = $field->get_entry_inputs() ?: array();
			foreach ( $entry_inputs as $input ) {
				$input['name'] = $allowsPrepopulate ? $input['name'] : '';
				$inputs[]      = $input;
			}
		} catch ( Error ) {
			$inputs = array();
		}

		$inputs = array_values(
			array_filter(
				$inputs,
				fn ( $input ) => ! isset( $input['isHidden'] ) || ! $input['isHidden']
			)
		);

		$named_inputs = array_filter(
			$inputs,
			fn ( $input ) => ! empty( $input['name'] )
		);

		$subfields = array();
		if ( count( $named_inputs ) ) {
			$count = count( $inputs );
			for ( $i = 1; $i <= $count; $i++ ) {
				$input = $inputs[ $i - 1 ];

				$input_label = implode(
					' ',
					array(
						$label,
						$input['label'] ? "({$input['label']})" : "($i)",
					)
				);

				$input_name = $input['name'] ?: $input_label;

				$subfields[] = $this->serialize_field(
					(object) array_merge(
						(array) $field,
						$input,
						array(
							'id'         => $input['id'],
							'inputName'  => $input_name,
							'label'      => $input_label,
							'adminLabel' => $input_label,
							'type'       => 'text',
							'schema'     => array( 'type' => 'string' ),
						)
					)
				);
			}
		}

		switch ( $field->type ) {
			case 'list':
			case 'checkbox':
			case 'multiselect':
			case 'multi_choice':
			case 'image_choice':
			case 'option':
			case 'select':
			case 'radio':
				$type = 'select';
				break;
			case 'number':
			case 'total':
			case 'quantity':
				$type = 'number';
				break;
			case 'consent':
				$type = 'checkbox';
				break;
			case 'fileupload':
				$type = 'file';
				break;
			case 'phone':
				$type = 'tel';
				break;
			case 'email':
				$type = 'email';
				break;
			case 'website':
				$type = 'url';
				break;
			case 'date':
				$type = 'date';
				break;
			case 'textarea':
				$type = $field->type;
				break;
			case 'address':
			case 'product':
			case 'name':
			case 'shipping':
			default:
				$type = 'text';
				break;
		}

		$field = apply_filters(
			'forms_bridge_form_field_data',
			array(
				'id'          => $field->id,
				'type'        => $type,
				'name'        => trim( $name ),
				'label'       => trim( $label ),
				'required'    => $field->isRequired,
				'options'     => $options,
				'inputs'      => $inputs,
				'is_file'     => 'fileupload' === $field->type,
				'is_multi'    => $this->is_multi_field( $field ),
				'conditional' => $field->conditionalLogic['enabled'] ?? false,
				'format'      => 'date' === $field->type ? 'yyyy-mm-dd' : '',
				'schema'      => $this->field_value_schema( $field ),
				'basetype'    => $field->type,
			),
			$field,
			'gf'
		);

		if ( ! empty( $subfields ) && ( $allowsPrepopulate || 'list' === $field['type'] ) ) {
			foreach ( $subfields as &$sf ) {
				$sf['parent'] = $field;
			}
		}

		return $field;
	}

	/**
	 * Checks if a filed is multi value field.
	 *
	 * @param GF_Field $field Target field instance.
	 *
	 * @return boolean
	 */
	private function is_multi_field( $field ) {
		if ( 'fileupload' === $field->type ) {
			return $field->multipleFiles ?? false;
		}

		if ( isset( $field->storageType ) && 'json' === $field->storageType ) {
			return true;
		}

		// if (isset($field->choiceLimit)) {
		// if ($field->choiceLimit === 'unlimited') {
		// return true;
		// } elseif ($field->choiceLimit === 'exactly' && $field->choiceLimitNumber > 1) {
		// return true;
		// }
		// }

		if ( 'list' === $field->type ) {
			return true;
		}

		if ( in_array( $field->inputType, array( 'list', 'checkbox' ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets the field value JSON schema.
	 *
	 * @param GF_Field $field Field instance.
	 *
	 * @return array JSON schema of the value of the field.
	 */
	private function field_value_schema( $field ) {
		switch ( $field->type ) {
			case 'list':
				if ( ! empty( $field->choices ) ) {
					return array(
						'type'            => 'array',
						'items'           => array(
							'type'       => 'object',
							'properties' => array_reduce(
								$field->choices,
								static function ( $choices, $choice ) {
									$choices[ $choice['value'] ] = array(
										'type' => 'string',
									);
									return $choices;
								},
								array()
							),
						),
						'additionalItems' => true,
					);
				}

				return array(
					'type'            => 'array',
					'items'           => array( 'type' => 'string' ),
					'additionalItems' => true,
				);
			case 'checkbox':
			case 'multiselect':
				$items = array();
				$count = count( $field->choices );
				for ( $i = 0; $i < $count; $i++ ) {
					$items[] = array( 'type' => 'string' );
				}

				return array(
					'type'            => 'array',
					'items'           => $items,
					'additionalItems' => false,
				);
			case 'multi_choice':
			case 'image_choice':
			case 'option':
				if ( $this->is_multi_field( $field ) ) {
					if ( 'range' === $field->choiceLimit ) {
						$maxItems = $field->choiceLimitMax;
					} elseif ( 'exactly' === $field->choiceLimit ) {
						$maxItems = $field->choiceLimitNumber;
					} else {
						$maxItems = count( $field->choices );
					}

					$items = array();
					for ( $i = 0; $i < $maxItems; $i++ ) {
						$items[] = array( 'type' => 'string' );
					}

					return array(
						'type'            => 'array',
						'items'           => $items,
						'additionalItems' => false,
					);
				}

				return array( 'type' => 'string' );
			case 'select':
			case 'radio':
			case 'address':
			case 'website':
			case 'product':
			case 'email':
			case 'textarea':
			case 'name':
			case 'shipping':
				return array( 'type' => 'string' );
			case 'number':
			case 'total':
			case 'quantity':
				return array( 'type' => 'number' );
			case 'fileupload':
				return;
			case 'consent':
				return array( 'type' => 'boolean' );
			default:
				return array( 'type' => 'string' );
		}
	}

	/**
	 * Serializes the current form's submission data.
	 *
	 * @param array $submission GF form submission.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public function serialize_submission( $submission, $form_data ) {
		$data = array();

		$has_total = array_search(
			'total',
			array_map(
				static function ( $field ) {
					return $field['basetype'];
				},
				$form_data['fields']
			)
		);

		$has_quantity = array_search(
			'quantity',
			array_map(
				static function ( $field ) {
					return $field['basetype'];
				},
				$form_data['fields']
			),
			true
		);

		foreach ( $form_data['fields'] as $field ) {
			if ( $field['is_file'] ) {
				continue;
			}

			$input_name = $field['name'];
			$inputs     = $field['inputs'];

			if ( ! empty( $inputs ) ) {
				$values = array();
				foreach ( $inputs as $input ) {
					if ( ! $this->isset( $input['id'] ) ) {
						continue;
					}

					$value = rgar( $submission, (string) $input['id'] );
					if ( $input_name && $value ) {
						$value = $this->format_value(
							$value,
							$field['basetype'],
							$input
						);

						if ( null !== $value ) {
							$values[] = $value;
						}
					}
				}

				if ( 'consent' === $field['basetype'] ) {
					$data[ $input_name ] = boolval( $values[0] ?? false );
				} elseif ( 'name' === $field['basetype'] ) {
					$data[ $input_name ] = implode( ' ', $values );
				} elseif ( 'product' === $field['basetype'] ) {
					if ( $has_total ) {
						$data[ $input_name ] = $values[0];
					} else {
						if ( $has_quantity ) {
							$values = array_slice( $values, 0, 2 );
						}

						$data[ $input_name ] = implode( '|', $values );
					}
				} elseif ( 'address' === $field['basetype'] ) {
					$data[ $input_name ] = implode( ', ', $values );
				} else {
					$data[ $input_name ] = $values;
				}
			} else {
				/* simple fields */
				$isset = $this->isset( $field['id'] );
				if ( ! $isset ) {
					continue;
				}

				if ( $input_name ) {
					$raw_value           = rgar( $submission, (string) $field['id'] );
					$data[ $input_name ] = $this->format_value(
						$raw_value,
						$field['basetype']
					);
				}
			}
		}

		return $data;
	}

	/**
	 * Formats field values with noop fallback.
	 *
	 * @param mixed  $value Field's value.
	 * @param string $field_type GF field type.
	 * @param array  $input Field's input data.
	 *
	 * @return mixed Formatted value.
	 */
	private function format_value( $value, $field_type, $input = null ) {
		try {
			switch ( $field_type ) {
				case 'consent':
					if ( preg_match( '/\.1$/', $input['id'] ) ) {
						return '1' === $value;
					}

					return null;
				case 'hidden':
					$number_val = (float) $value;
					if ( strval( $number_val ) === $value ) {
						return $number_val;
					}
					break;
				case 'quantity':
				case 'number':
					return (float) preg_replace( '/[^0-9\.,]/', '', $value );
				case 'list':
					return maybe_unserialize( $value );
				case 'multiselect':
					return json_decode( $value );
				case 'product':
				case 'option':
				case 'shipping':
					return $value;
				// return explode('|', $value)[0];
			}
		// phpcs:disable Generic.CodeAnalysis.EmptyStatement
		} catch ( TypeError ) {
			/* do nothing */
		}
		// phpcs:enable Generic.CodeAnalysis.EmptyStatement

		return $value;
	}

	/**
	 * Gets the current submission's uploaded files.
	 *
	 * @param array $submission GF submission data.
	 * @param array $form_data Form data.
	 *
	 * @return array Uploaded files data.
	 */
	protected function submission_uploads( $submission, $form_data ) {
		return array_reduce(
			array_filter(
				$form_data['fields'],
				function ( $field ) {
					return $field['is_file'];
				}
			),
			function ( $carry, $field ) use ( $submission, $form_data ) {
				$upload_path = GFFormsModel::get_upload_path( $form_data['id'] );
				$upload_url  = GFFormsModel::get_upload_url( $form_data['id'] );

				$urls = $submission[ $field['id'] ] ?? array();
				$urls = $field['is_multi'] ? json_decode( $urls, true ) : array( $urls );

				if ( ! is_array( $urls ) ) {
					return $carry;
				}

				$paths = array();
				foreach ( $urls as $url ) {
					$path = str_replace( $upload_url, $upload_path, $url );
					if ( is_file( $path ) ) {
						$paths[] = $path;
					}
				}

				if ( ! empty( $paths ) ) {
					$carry[ $field['name'] ] = array(
						'path'     => $field['is_multi'] ? $paths : $paths[0],
						'is_multi' => $field['is_multi'],
					);
				}

				return $carry;
			},
			array()
		);
	}

	/**
	 * Helper function to check if field is set on $_POST super global.
	 *
	 * @param string $field_id ID of the field.
	 *
	 * @return boolean
	 */
	private function isset( $field_id ) {
		$key = 'input_' . implode( '_', explode( '.', $field_id ) );
		return isset( $_POST[ $key ] ) || defined( 'WP_TESTS_DOMAIN' );
	}

	/**
	 * Decorate bridge's tempalte form fields data to be created as gf fields.
	 *
	 * @param array $fields Array with bridge's template form fields data.
	 *
	 * @return array Decorated array of fields.
	 */
	private function prepare_fields( $fields ) {
		$gf_fields = array();
		$count     = count( $fields );
		for ( $i = 0; $i < $count; $i++ ) {
			$id    = $i + 1;
			$field = $fields[ $i ];
			$args  = array(
				$id,
				$field['name'],
				$field['label'] ?? '',
				$field['required'] ?? false,
			);

			switch ( $field['type'] ) {
				case 'hidden':
					if ( isset( $field['value'] ) ) {
						if ( is_bool( $field['value'] ) ) {
							$field['value'] = $field['value'] ? '1' : '0';
						}

						$args[]      = (string) $field['value'];
						$gf_fields[] = $this->hidden_field( ...$args );
					}

					break;
				case 'number':
					$constraints = array(
						'rangeMin'     => $field['min'] ?? '',
						'rangeMax'     => $filed['max'] ?? '',
						'rangeStep'    => $field['step'] ?? '1',
						'defaultValue' => floatval( $field['default'] ?? 0 ),
					);

					$args[]      = $constraints;
					$gf_fields[] = $this->number_field( ...$args );
					break;
				case 'email':
					$gf_fields[] = $this->email_field( ...$args );
					break;
				case 'tel':
					$gf_fields[] = $this->tel_field( ...$args );
					break;
				case 'select':
					$args[]      = $field['options'] ?? array();
					$args[]      = $field['is_multi'] ?? false;
					$gf_fields[] = $this->select_field( ...$args );
					break;
				case 'checkbox':
					$gf_fields[] = $this->checkbox_field( ...$args );
					break;
				case 'textarea':
					$gf_fields[] = $this->textarea_field( ...$args );
					break;
				case 'url':
					$gf_fields[] = $this->url_field( ...$args );
					break;
				case 'date':
					$gf_fields[] = $this->date_field( ...$args );
					break;
				case 'file':
					$args[]      = $field['is_multi'] ?? false;
					$args[]      = $field['filetypes'] ?? '';
					$gf_fields[] = $this->file_field( ...$args );
					break;
				// case 'text':
				default:
					$gf_fields[] = $this->text_field( ...$args );
			}
		}

		return $gf_fields;
	}

	/**
	 * Returns a default field array data. Used as template for the field creation methods.
	 *
	 * @param string  $type Field type.
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 *
	 * @return array
	 */
	private function field_template( $type, $id, $name, $label, $required ) {
		return array(
			'type'                  => $type,
			'id'                    => (int) $id,
			'isRequired'            => (bool) $required,
			'size'                  => 'large',
			'errorMessage'          => __( 'Please supply a valid value', 'forms-bridge' ),
			'label'                 => $label,
			'formId'                => 84,
			'inputType'             => '',
			'displayOnly'           => '',
			'inputs'                => null,
			'choices'               => '',
			'conditionalLogic'      => '',
			'labelPlacement'        => '',
			'descriptionPlacement'  => '',
			'subLabelPlacement'     => '',
			'placeholder'           => '',
			'multipleFiles'         => false,
			'maxFiles'              => '',
			'calculationFormula'    => '',
			'calculationRounding'   => '',
			'enableCalculation'     => '',
			'disableQuantity'       => false,
			'displayAllCategories'  => false,
			'inputMask'             => false,
			'inputMaskValue'        => '',
			'allowsPrepopulate'     => true,
			'useRichTextEditor'     => false,
			'visibility'            => 'visible',
			'fields'                => '',
			'inputMaskIsCustom'     => false,
			'layoutGroupId'         => '17f293c9',
			'autocompleteAttribute' => '',
			'emailConfirmEnabled'   => false,
			'adminLabel'            => '',
			'description'           => '',
			'maxLength'             => '',
			'cssClass'              => '',
			'inputName'             => $name,
			'noDuplicates'          => false,
			'defaultValue'          => '',
			'enableAutocomplete'    => false,
		);
	}

	/**
	 * Returns a valid email field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 *
	 * @return array
	 */
	private function email_field( $id, $name, $label, $required ) {
		return array_merge(
			$this->field_template( 'email', $id, $name, $label, $required ),
			array(
				'errorMessage'       => __(
					'please supply a valid email address',
					'forms-bridge'
				),
				'enableAutocomplete' => true,
			)
		);
	}

	/**
	 * Returns a valid tel field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 *
	 * @return array
	 */
	private function tel_field( $id, $name, $label, $required ) {
		return array_merge(
			$this->field_template( 'phone', $id, $name, $label, $required ),
			array(),
		);
	}

	/**
	 * Returns a valid textarea field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 *
	 * @return array
	 */
	private function textarea_field( $id, $name, $label, $required ) {
		return $this->field_template( 'textarea', $id, $name, $label, $required );
	}

	/**
	 * Returns a valid multi select field data, as a select field if is single, as
	 * a checkbox field if is multiple.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 * @param array   $options Options data.
	 * @param bool    $is_multi Is field multi value.
	 *
	 * @return array
	 */
	private function select_field(
		$id,
		$name,
		$label,
		$required,
		$options,
		$is_multi
	) {
		$choices = array_map(
			function ( $opt ) {
				return array(
					'text'       => esc_html( $opt['label'] ),
					'value'      => $opt['value'],
					'isSelected' => false,
					'price'      => '',
				);
			},
			$options
		);

		if ( $is_multi ) {
			$inputs = array();
			$count  = count( $choices );
			for ( $i = 0; $i < $count; $i++ ) {
				$input_id = $i + 1;
				$inputs[] = array(
					'id'    => $id . '.' . $input_id,
					'label' => $choices[ $i ]['label'],
					'name'  => '',
				);
			}

			return array_merge(
				$this->field_template(
					'checkbox',
					$id,
					$name,
					$label,
					$required
				),
				array(
					'choices'           => $choices,
					'inputs'            => $inputs,
					'enableChoiceValue' => true,
				)
			);
		} else {
			return array_merge(
				$this->field_template( 'select', $id, $name, $label, $required ),
				array(
					'choices'           => $choices,
					'enableChoiceValue' => true,
				)
			);
		}
	}

	/**
	 * Returns a valid file-upload field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 * @param boolean $is_multi Indicates if the field supports multiple values.
	 * @param string  $filetypes String with allowed file extensions separated by commas.
	 *
	 * @return array
	 */
	private function file_field(
		$id,
		$name,
		$label,
		$required,
		$is_multi,
		$filetypes
	) {
		return array_merge(
			$this->field_template( 'fileupload', $id, $name, $label, $required ),
			array(
				'allowedExtensions' => (string) $filetypes,
				'multipleFiles'     => (bool) $is_multi,
			)
		);
	}

	/**
	 * Returns a valid hidden field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label (unused).
	 * @param boolean $required Is field required (unused).
	 * @param string  $value Field's default value.
	 *
	 * @return array
	 */
	private function hidden_field( $id, $name, $label, $required, $value ) {
		return array_merge(
			$this->field_template( 'hidden', $id, $name, $name, true ),
			array(
				'inputType'    => 'hidden',
				'defaultValue' => $value,
			)
		);
	}

	/**
	 * Returns a valid hidden field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 *
	 * @return array
	 */
	private function url_field( $id, $name, $label, $required ) {
		return $this->field_template( 'website', $id, $name, $label, $required );
	}

	/**
	 * Returns a valid hidden field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 *
	 * @return array
	 */
	private function text_field( $id, $name, $label, $required ) {
		return array_merge(
			$this->field_template( 'text', $id, $name, $label, $required ),
			array(
				'inputType' => 'text',
			)
		);
	}

	/**
	 * Returns a valid date field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 *
	 * @return array
	 */
	private function date_field( $id, $name, $label, $required ) {
		return array_merge(
			$this->field_template( 'date', $id, $name, $label, $required ),
			array(
				'dateType'            => 'datepicker',
				'calendarIconType'    => 'none',
				'dateFormatPlacement' => 'below',
				'dateFormat'          => 'mdy',
			)
		);
	}

	/**
	 * Returns a valid hidden field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 * @param array   $constraints Input constraints.
	 *
	 * @return array
	 */
	private function number_field( $id, $name, $label, $required, $constraints ) {
		return array_merge(
			$this->field_template( 'number', $id, $name, $label, $required ),
			array_merge(
				$constraints,
				array(
					'inputType'    => 'number',
					'numberFormat' => 'decimal_dot',
				)
			)
		);
	}

	/**
	 * Returns a valid checkbox field data.
	 *
	 * @param int     $id Field id.
	 * @param string  $name Input name.
	 * @param string  $label Field label.
	 * @param boolean $required Is field required.
	 *
	 * @return array
	 */
	private function checkbox_field( $id, $name, $label, $required ) {
		return array_merge(
			$this->field_template( 'consent', $id, $name, $label, $required ),
			array(
				'choices' => array(
					array(
						'isSelected' => false,
						'price'      => '',
						'text'       => __( 'Checked', 'forms-bridge' ),
						'value'      => '1',
					),
				),
			),
		);
	}
}

add_filter(
	'gform_field_content',
	function ( $field_content, $field, $value, $entry_id, $form_id ) {
		if ( 'number' !== $field->type ) {
			return $field_content;
		}

		if ( empty( $field->rangeStep ) ) {
			return $field_content;
		}

		$step = (int) $field->rangeStep;
		return str_replace( "step='any'", "step='{$step}'", $field_content );
	},
	10,
	5
);

GF_Integration::setup();
