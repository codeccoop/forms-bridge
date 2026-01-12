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
use stdClass;
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

		$repeater = null;
		foreach ( $frm_fields as $frm_field ) {
			if ( intval( $form_id ) !== intval( $frm_field->form_id ) ) {
				if ( $repeater && $repeater->form_id !== $frm_field->form_id ) {
					$fields[] = $this->serialize_field( $repeater );
					$repeater = null;
				}

				if ( ! $repeater ) {
					$repeater_form = FrmForm::getOne( $frm_field->form_id );

					$repeater = (object) array(
						'id'            => $frm_field->field_options['in_section'],
						'field_key'     => $repeater_form->form_key,
						'name'          => $repeater_form->name,
						'description'   => $repeater_form->description,
						'options'       => '',
						'required'      => '0',
						'fields'        => array(),
						'type'          => 'repeater',
						'form_id'       => $frm_field->form_id,
						'field_options' => array( 'multiple' => true ),
					);
				}

				$repeater_field = $this->serialize_field( $frm_field );
				if ( $repeater_field ) {
					$repeater->fields[] = $repeater_field;
				}

				continue;
			}

			if ( $repeater ) {
				$fields[] = $this->serialize_field( $repeater );
				$repeater = null;
			}

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
		// Skip non-input fields.
		if ( in_array( $field->type, array( 'data', 'summary', 'break', 'end_divider', 'divider', 'html', 'captcha', 'submit' ), true ) ) {
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
			case 'form':
			case 'repeater':
				$type = 'mixed';
				break;
			case 'checkbox':
			case 'select':
			case 'radio':
			case 'lookup':
				$type = 'select';
				break;
			case 'range':
				if ( $field->field_options['is_range_slider'] ?? false ) {
					return 'select';
				}

				return 'number';
			case 'star':
			case 'number':
			case 'scale':
			case 'quantity':
			case 'total':
			case 'user_id':
				$type = 'number';
				break;
			case 'date':
			case 'file':
			case 'email':
			case 'url':
				$type = $field->type;
				break;
			case 'phone':
				$type = 'tel';
				break;
			case 'rte':
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
				'conditional' => ! empty( $field->field_options['hide_field'] ) && ! empty( $field->field_options['hide_opt'] ),
				'format'      => 'date' === $field->type ? 'yyyy-mm-dd' : '',
				'schema'      => $this->field_value_schema( $field ),
				'basetype'    => $field->type,
				'form_id'     => $field->form_id,
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
			return boolval( $field->field_options['multiple'] ?? false );
		}

		if ( 'range' === $field->type ) {
			return boolval( $field->options['is_range_slider'] ?? false );
		}

		if ( in_array( $field->type, array( 'repeater', 'checkbox', 'address', 'credit_card' ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets the field value JSON schema.
	 *
	 * @param object $field Field instance.
	 *
	 * @return array|null JSON schema of the value of the field.
	 */
	private function field_value_schema( $field ) {
		switch ( $field->type ) {
			case 'form':
				$embedded      = FrmForm::getOne( $field->field_options['form_select'] );
				$embedded_data = $this->serialize_form( $embedded );

				$schema = array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
				);

				foreach ( $embedded_data['fields'] as $embedded_field ) {
					$schema['properties'][ $embedded_field['name'] ] = $embedded_field['schema'];
				}

				return $schema;
			case 'repeater':
				$schema = array(
					'type'            => 'array',
					'items'           => array(
						'type'                 => 'object',
						'properties'           => array(),
						'additionalProperties' => false,
					),
					'additionalItems' => true,
				);

				foreach ( $field->fields as $subfield ) {
					$schema['items']['properties'][ $subfield['name'] ] = $subfield['schema'];
				}

				return $schema;
			case 'star':
			case 'scale':
			case 'range':
			case 'quantity':
				return array( 'type' => 'integer' );
			case 'total':
			case 'number':
				return array( 'type' => 'number' );
			case 'checkbox':
				return array(
					'type'            => 'array',
					'items'           => array( 'type' => 'string' ),
					'additionalItems' => false,
				);
			case 'credit_card':
				return array(
					'type'                 => 'object',
					'properties'           => array(
						'cc'    => 'string',
						'cvc'   => 'string',
						'month' => 'string',
						'year'  => 'string',
					),
					'additionalProperties' => false,
				);
			case 'address':
				return array(
					'type'                 => 'object',
					'properties'           => array(
						'line1'   => 'string',
						'line2'   => 'string',
						'city'    => 'string',
						'country' => 'string',
						'zip'     => 'string',
					),
					'additionalProperties' => false,
				);
			case 'select':
				if ( $field->field_options['multiple'] ) {
					return array(
						'type'            => 'array',
						'items'           => array( 'type' => 'string' ),
						'additionalItems' => false,
					);
				} else {
					return array( 'type' => 'string' );
				}
			case 'name':
				return array(
					'type'                 => 'object',
					'properties'           => array(
						'first' => 'string',
						'last'  => 'string',
					),
					'additionalProperties' => false,
				);
			case 'hidden':
				$type = 'string';

				if ( 'number' === $field->field_options['format'] ) {
					$type = 'number';
				}

				return array( 'type' => $type );
			case 'file':
				return null;
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

		$by_field_id = array();
		foreach ( $submission['values'] as $submission_value ) {
			$by_field_id[ $submission_value->field_id ] = $submission_value;
		}

		foreach ( $form_data['fields'] as $field ) {
			if ( $field['is_file'] ) {
				continue;
			}

			$field_name = $field['name'];
			$field_id   = $field['id'];
			$value      = $by_field_id[ $field_id ] ?? null;

			if ( null !== $value ) {
				if ( 'form' === $field['basetype'] ) {
					$value               = maybe_unserialize( $value->meta_value );
					$entry_id            = reset( $value );
					$entry               = FrmEntry::getOne( $entry_id );
					$embedded_form       = $this->get_form_by_id( $entry->form_id );
					$embedded_submission = array(
						'id'     => $entry_id,
						'values' => FrmEntryMeta::get_entry_meta_info( $entry_id ),
					);
					$embedded_data       = $this->serialize_submission( $embedded_submission, $embedded_form );

					$data[ $field['name'] ] = $embedded_data;
				} elseif ( 'repeater' === $field['basetype'] ) {
					$entries_ids   = maybe_unserialize( $value->meta_value );
					$repeater_form = $this->get_form_by_id( $field['form_id'] );

					$repeater_data = array();
					foreach ( $entries_ids as $entry_id ) {
						$repeater_submission = array(
							'id'     => $entry_id,
							'values' => FrmEntryMeta::get_entry_meta_info( $entry_id ),
						);

						$repeater_data[] = $this->serialize_submission( $repeater_submission, $repeater_form );
					}

					$data[ $field['name'] ] = $repeater_data;
				} else {
					$value = $this->format_value( $value->meta_value, $field['basetype'] );

					if ( null !== $value ) {
						$data[ $field_name ] = $value;
					}
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
				case 'star':
				case 'scale':
				case 'quantity':
					return (int) $value;
				case 'range':
					if ( false !== strpos( $value, ',' ) ) {
						return array_map( 'intval', explode( ',', $value ) );
					}

					return (int) $value;
				case 'total':
				case 'number':
					return (float) $value;
				case 'checkbox':
					$value = maybe_unserialize( $value );

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
				case 'credit_card':
				case 'address':
				case 'name':
					return maybe_unserialize( $value );
				case 'user_id':
					$value = maybe_unserialize( $value );
					if ( is_array( $value ) ) {
						return intval( $value['unique_id'] ?? null );
					}

					return (int) $value;
				case 'hidden':
					if ( is_numeric( $value ) ) {
						return (float) $value;
					}

					return $value;
				default:
					return (string) $value;
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
		global $frm_vars;

		$uploads = array();

		$media_id = $frm_vars['media_id'] ?? array();
		foreach ( $media_id as $field_id => $attachment_ids ) {
			$paths = array();

			if ( ! is_array( $attachment_ids ) ) {
				$attachment_ids = array( $attachment_ids );
			}

			foreach ( $form_data['fields'] as $field ) {
				if ( intval( $field['id'] ) === intval( $field_id ) ) {
					foreach ( $attachment_ids as $attachment_id ) {
						$attachment_path = get_attached_file( $attachment_id );

						if ( $attachment_path && is_file( $attachment_path ) ) {
							if ( ! is_readable( $attachment_path ) ) {
								global $wp_filesystem;

								$tmpdir = get_temp_dir();

								clearstatcache();
								$attachment_chmod = fileperms( $attachment_path ) & 0777;

								$wp_filesystem->chmod( $attachment_path, 0400 );

								$attachment_filename = basename( $attachment_path );
								$attachment_contents = $wp_filesystem->get_contents( $attachment_path );

								$wp_filesystem->chmod( $attachment_path, $attachment_chmod );

								$attachment_path = $tmpdir . $attachment_filename;
								$wp_filesystem->put_contents( $attachment_path, $attachment_contents );
							}

							$paths[] = $attachment_path;

							if ( ! is_readable( $attachment_path ) ) {
								global $wp_filesystem;
								$wp_filesystem->chown( $attachment_path, 0400 );
								$protected_paths[] = $attachment_path;
							}
						}
					}

					break;
				}
			}

			if ( ! empty( $paths ) ) {
				$uploads[ $field['name'] ] = array(
					'path'     => $field['is_multi'] ? $paths : $paths[0],
					'is_multi' => $field['is_multi'],
				);
			}
		}

		return $uploads;
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
		return $this->field_template( 'url', $args );
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
