<?php
/**
 * Class Formidable_Integration
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE\Formidable;

use FBAPI;
use FORMS_BRIDGE\Forms_Bridge;
use FORMS_BRIDGE\Integration as BaseIntegration;
use FrmForm;
use FrmAppHelper;
use FrmEntry;
use FrmEntryMeta;
use FrmField;
use TypeError;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Formidable Forms integration.
 */
class Formidable_Integration extends BaseIntegration {
	/**
	 * Handles integration name.
	 *
	 * @var string
	 */
	const NAME = 'formidable';

	/**
	 * Handles integration title.
	 *
	 * @var string
	 */
	const TITLE = 'Formidable Forms';

	/**
	 * Binds after submission hook to the do_submission routine.
	 */
	protected function init() {
		add_action(
			'frm_process_entry',
			function () {
				Forms_Bridge::do_submission();
			},
			10,
			0
		);
	}

	/**
	 * Retrives the current form's data.
	 *
	 * @return array|null
	 */
	public function form() {
		global $frm_vars;

		$form_id = FrmAppHelper::get_post_param( 'form_id', '', 'absint' );

		if ( ! $form_id || ! isset( $frm_vars['form_params'][ $form_id ] ) ) {
			return null;
		}

		$form = FrmForm::getOne( $form_id );
		// $params = $frm_vars['form_params'][ $form_id ];

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
		$form = FrmForm::getOne( $form_id );
		if ( ! $form ) {
			return null;
		}

		// $params = FrmForm::get_params( $form );
		return $this->serialize_form( $form );
	}

	/**
	 * Retrives available forms' data.
	 *
	 * @return array Collection of form data array representations.
	 */
	public function forms() {
		$forms = FrmForm::get_published_forms();
		return array_map(
			function ( $form ) {
				return $this->serialize_form( $form );
			},
			$forms
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
			return null;
		}

		$form_data = array(
			'name'        => $data['title'],
			'description' => $data['description'] ?? '',
			'status'      => 'published',
			'form_key'    => sanitize_title( $data['title'] ),
			'field_data'  => $this->prepare_fields( $data['fields'] ),
		);

		$form_id = FrmForm::create( $form_data );

		if ( is_wp_error( $form_id ) ) {
			return null;
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
		return (bool) FrmForm::destroy( $form_id );
	}

	/**
	 * Retrives the current submission ID.
	 *
	 * @return string|null
	 */
	public function submission_id() {
		global $frm_vars;

		$form_id = FrmAppHelper::get_post_param( 'form_id', '', 'absint' );

		if ( ! $form_id || ! isset( $frm_vars['created_entries'][ $form_id ]['entry_id'] ) ) {
			return null;
		}

		return (string) $frm_vars['created_entries'][ $form_id ]['entry_id'];
	}

	/**
	 * Retrives the current submission data.
	 *
	 * @param boolean $raw Control if the submission is serialized before exit.
	 *
	 * @return array|null
	 */
	public function submission( $raw = false ) {
		global $frm_vars;

		$form_id = FrmAppHelper::get_post_param( 'form_id', '', 'absint' );

		if ( ! $form_id || ! isset( $frm_vars['created_entries'][ $form_id ]['entry_id'] ) ) {
			return null;
		}

		$entry_id = $frm_vars['created_entries'][ $form_id ]['entry_id'];

		$form = $this->form();

		$submission = array(
			'id'     => $entry_id,
			'values' => FrmEntryMeta::get_entry_meta_info( $entry_id ),
		);

		if ( $raw ) {
			$submission['entry'] = FrmEntry::getOne( $entry_id );
			return $submission;
		}

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

		$entry_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $entry_id ) {
			return null;
		}

		$submission = FrmEntry::getOne( $entry_id );
		if ( ! $submission ) {
			return null;
		}

		$form = $this->form();
		return $this->submission_uploads( $submission, $form );
	}

	/**
	 * Serializes Formidable form's data.
	 *
	 * @param object $form Formidable form data.
	 *
	 * @return array
	 */
	public function serialize_form( $form ) {
		$form_id = (int) $form->id;

		$fields     = array();
		$frm_fields = FrmField::get_all_for_form( $form_id );

		foreach ( $frm_fields as $frm_field ) {
			$field = $this->serialize_field( $frm_field );

			if ( $field ) {
				$field  = wp_is_numeric_array( $field ) ? $field : array( $field );
				$fields = array_merge( $fields, $field );
			}
		}

		return apply_filters(
			'forms_bridge_form_data',
			array(
				'_id'     => 'formidable:' . $form_id,
				'id'      => $form_id,
				'title'   => $form->name,
				'bridges' => FBAPI::get_form_bridges( $form_id, 'formidable' ),
				'fields'  => $fields,
			),
			$form,
			'formidable'
		);
	}

	/**
	 * Serializes a Formidable field as array data.
	 *
	 * @param object $field Field object instance.
	 *
	 * @return array|null
	 */
	private function serialize_field( $field ) {
		// Skip non-input fields
		if ( in_array( $field->type, array( 'divider', 'html', 'captcha', 'submit' ), true ) ) {
			return null;
		}

		$label = $field->name ?: $field->description;
		$name  = $field->field_key ?: $label;

		$options = array();
		if ( isset( $field->options ) && is_array( $field->options ) ) {
			foreach ( $field->options as $option ) {
				$options[] = array(
					'value' => $option['value'],
					'label' => $option['label'],
				);
			}
		}

		switch ( $field->type ) {
			case 'checkbox':
			case 'select':
			case 'radio':
				$type = 'select';
				break;
			case 'number':
				$type = 'number';
				break;
			case 'file':
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
				$type = 'textarea';
				break;
			case 'hidden':
				$type = 'hidden';
				break;
			default:
				$type = 'text';
		}

		$field = apply_filters(
			'forms_bridge_form_field_data',
			array(
				'id'          => $field->id,
				'type'        => $type,
				'name'        => trim( $name ),
				'label'       => trim( $label ),
				'required'    => $field->required,
				'options'     => $options,
				'is_file'     => 'file' === $field->type,
				'is_multi'    => $this->is_multi_field( $field ),
				'conditional' => false,
				'format'      => 'date' === $field->type ? 'yyyy-mm-dd' : '',
				'schema'      => $this->field_value_schema( $field ),
				'basetype'    => $field->type,
			),
			$field,
			'formidable'
		);

		return $field;
	}

	/**
	 * Checks if a field is multi-value field.
	 *
	 * @param object $field Target field instance.
	 *
	 * @return boolean
	 */
	private function is_multi_field( $field ) {
		if ( 'file' === $field->type ) {
			return $field->multiple ?? false;
		}

		if ( 'checkbox' === $field->type ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets the field value JSON schema.
	 *
	 * @param object $field Field instance.
	 *
	 * @return array JSON schema of the value of the field.
	 */
	private function field_value_schema( $field ) {
		switch ( $field->type ) {
			case 'checkbox':
				return array(
					'type'            => 'array',
					'items'           => array( 'type' => 'string' ),
					'additionalItems' => false,
				);
			case 'select':
				if ( $field->multiple ) {
					return array(
						'type'            => 'array',
						'items'           => array( 'type' => 'string' ),
						'additionalItems' => false,
					);
				} else {
					return array( 'type' => 'string' );
				}
			case 'radio':
				return array( 'type' => 'string' );
			case 'number':
				return array( 'type' => 'number' );
			case 'file':
				return array( 'type' => 'string' );
			case 'email':
				return array( 'type' => 'string' );
			case 'textarea':
				return array( 'type' => 'string' );
			case 'hidden':
				return array( 'type' => 'string' );
			case 'date':
				return array( 'type' => 'string' );
			case 'phone':
				return array( 'type' => 'string' );
			case 'website':
				return array( 'type' => 'string' );
			default:
				return array( 'type' => 'string' );
		}
	}

	/**
	 * Serializes the current form's submission data.
	 *
	 * @param object $submission Formidable form submission.
	 * @param array  $form_data Form data.
	 *
	 * @return array
	 */
	public function serialize_submission( $submission, $form_data ) {
		$data = array();

		foreach ( $form_data['fields'] as $field ) {
			if ( $field['is_file'] ) {
				continue;
			}

			$input_name = $field['name'];
			$field_id   = $field['id'];

			$value = $submission['values'][ $field_id ] ?? null;

			if ( null !== $value ) {
				$value = $this->format_value( $value->meta_value, $field['basetype'] );
				if ( null !== $value ) {
					$data[ $input_name ] = $value;
				}
			}
		}

		return $data;
	}

	/**
	 * Formats field values with noop fallback.
	 *
	 * @param mixed  $value Field's value.
	 * @param string $field_type Formidable field type.
	 *
	 * @return mixed Formatted value.
	 */
	private function format_value( $value, $field_type ) {
		try {
			switch ( $field_type ) {
				case 'number':
					return (float) $value;
				case 'checkbox':
					if ( is_array( $value ) ) {
						return array_filter( $value );
					} else {
						return array( $value );
					}
				case 'select':
					if ( is_array( $value ) ) {
						return array_filter( $value );
					} else {
						return $value;
					}
				case 'file':
					return $value;
				case 'hidden':
					return $value;
				case 'date':
					return $value;
				case 'email':
					return $value;
				case 'textarea':
					return $value;
				case 'phone':
					return $value;
				case 'website':
					return $value;
				case 'radio':
					return $value;
				case 'user_id':
					$value = maybe_unserialize( $value );
					return $value['unique_id'] ?? null;
				default:
					return $value;
			}
		} catch ( TypeError $e ) {
			return $value;
		}
	}

	/**
	 * Gets the current submission's uploaded files.
	 *
	 * @param object $submission Formidable submission data.
	 * @param array  $form_data Form data.
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
				$field_id = $field['id'];
				$value    = isset( $submission->metas[ $field_id ] ) ? $submission->metas[ $field_id ] : null;

				if ( ! $value ) {
					return $carry;
				}

				$upload_dir = wp_upload_dir();
				$paths      = array();

				if ( is_array( $value ) ) {
					foreach ( $value as $file_url ) {
						$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_url );
						if ( is_file( $file_path ) ) {
							$paths[] = $file_path;
						}
					}
				} else {
					$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $value );
					if ( is_file( $file_path ) ) {
						$paths[] = $file_path;
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
	 * Decorate bridge's template form fields data to be created as Formidable fields.
	 *
	 * @param array $fields Array with bridge's template form fields data.
	 *
	 * @return array Decorated array of fields.
	 */
	private function prepare_fields( $fields ) {
		$formidable_fields = array();
		$count             = count( $fields );
		for ( $i = 0; $i < $count; $i++ ) {
			$field = $fields[ $i ];
			$args  = array(
				'name'     => $field['name'],
				'label'    => $field['label'] ?? '',
				'required' => $field['required'] ?? false,
			);

			switch ( $field['type'] ) {
				case 'hidden':
					if ( isset( $field['value'] ) ) {
						$args['default_value'] = $field['value'];
					}
					$formidable_fields[] = $this->hidden_field( $args );
					break;
				case 'number':
					$formidable_fields[] = $this->number_field( $args );
					break;
				case 'email':
					$formidable_fields[] = $this->email_field( $args );
					break;
				case 'tel':
					$formidable_fields[] = $this->tel_field( $args );
					break;
				case 'select':
					$args['options']     = $field['options'] ?? array();
					$args['is_multi']    = $field['is_multi'] ?? false;
					$formidable_fields[] = $this->select_field( $args );
					break;
				case 'checkbox':
					$formidable_fields[] = $this->checkbox_field( $args );
					break;
				case 'textarea':
					$formidable_fields[] = $this->textarea_field( $args );
					break;
				case 'url':
					$formidable_fields[] = $this->url_field( $args );
					break;
				case 'date':
					$formidable_fields[] = $this->date_field( $args );
					break;
				case 'file':
					$args['is_multi']    = $field['is_multi'] ?? false;
					$args['filetypes']   = $field['filetypes'] ?? '';
					$formidable_fields[] = $this->file_field( $args );
					break;
				case 'text':
				default:
					$formidable_fields[] = $this->text_field( $args );
			}
		}

		return $formidable_fields;
	}

	/**
	 * Returns a default field array data. Used as template for the field creation methods.
	 *
	 * @param string $type Field type.
	 * @param array  $args Field arguments.
	 *
	 * @return array
	 */
	private function field_template( $type, $args ) {
		return array(
			'type'        => $type,
			'name'        => $args['name'],
			'label'       => $args['label'],
			'required'    => $args['required'],
			'description' => $args['description'] ?? '',
		);
	}

	/**
	 * Returns a valid email field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function email_field( $args ) {
		return $this->field_template( 'email', $args );
	}

	/**
	 * Returns a valid tel field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function tel_field( $args ) {
		return $this->field_template( 'phone', $args );
	}

	/**
	 * Returns a valid textarea field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function textarea_field( $args ) {
		return $this->field_template( 'textarea', $args );
	}

	/**
	 * Returns a valid multi select field data, as a select field if is single, as
	 * a checkbox field if is multiple.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function select_field( $args ) {
		$options = array();
		foreach ( $args['options'] as $option ) {
			$options[] = $option['label'];
		}

		if ( $args['is_multi'] ) {
			return array_merge(
				$this->field_template( 'checkbox', $args ),
				array( 'options' => $options )
			);
		} else {
			return array_merge(
				$this->field_template( 'select', $args ),
				array( 'options' => $options )
			);
		}
	}

	/**
	 * Returns a valid file-upload field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function file_field( $args ) {
		return array_merge(
			$this->field_template( 'file', $args ),
			array(
				'multiple'   => $args['is_multi'],
				'file_types' => $args['filetypes'],
			)
		);
	}

	/**
	 * Returns a valid hidden field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function hidden_field( $args ) {
		return array_merge(
			$this->field_template( 'hidden', $args ),
			array( 'default_value' => $args['default_value'] ?? '' )
		);
	}

	/**
	 * Returns a valid hidden field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function url_field( $args ) {
		return $this->field_template( 'website', $args );
	}

	/**
	 * Returns a valid hidden field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function text_field( $args ) {
		return $this->field_template( 'text', $args );
	}

	/**
	 * Returns a valid date field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function date_field( $args ) {
		return $this->field_template( 'date', $args );
	}

	/**
	 * Returns a valid hidden field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function number_field( $args ) {
		return $this->field_template( 'number', $args );
	}

	/**
	 * Returns a valid checkbox field data.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return array
	 */
	private function checkbox_field( $args ) {
		return array_merge(
			$this->field_template( 'checkbox', $args ),
			array( 'options' => array( __( 'Checked', 'forms-bridge' ) ) )
		);
	}
}

Formidable_Integration::setup();
