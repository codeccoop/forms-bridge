<?php
/**
 * Class WPCF7_Integration
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE\WPCF7;

use FBAPI;
use FORMS_BRIDGE\Forms_Bridge;
use FORMS_BRIDGE\Integration as BaseIntegration;
use WPCF7_ContactForm;
use WPCF7_Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * ContactForm7 integration.
 */
class WPCF7_Integration extends BaseIntegration {
	/**
	 * Handles integration name.
	 *
	 * @var string
	 */
	const NAME = 'wpcf7';

	/**
	 * Handles integration title.
	 *
	 * @var string
	 */
	const TITLE = 'Contact Form 7';

	/**
	 * Binds form submit hook to the do_submission routine.
	 */
	protected function init() {
		add_filter(
			'wpcf7_submit',
			function ( $form, $result ) {
				if (
					in_array(
						$result['status'],
						array( 'validation_failed', 'acceptance_missing', 'spam' ),
						true
					)
				) {
					return;
				}

				Forms_Bridge::do_submission();
			},
			10,
			2
		);
	}

	/**
	 * Retrives the current contact form's data.
	 *
	 * @return array $form_data Form data array representation.
	 */
	public function form() {
		$form = WPCF7_ContactForm::get_current();
		if ( ! $form ) {
			return null;
		}

		return $this->serialize_form( $form );
	}

	/**
	 * Retrives a contact form's data by ID.
	 *
	 * @param int $form_id Form ID.
	 * @return array $form_data Form data.
	 */
	public function get_form_by_id( $form_id ) {
		$form = WPCF7_ContactForm::get_instance( $form_id );
		if ( ! $form ) {
			return null;
		}

		return $this->serialize_form( $form );
	}

	/**
	 * Retrives available constact forms as form data.
	 *
	 * @return array $forms Collection of form data.
	 */
	public function forms() {
		$forms = WPCF7_ContactForm::find( array( 'post_status', 'publish' ) );
		return array_map(
			function ( $form ) {
				return $this->serialize_form( $form );
			},
			$forms
		);
	}

	/**
	 * Creates a form from the given template fields.
	 *
	 * @param array $data Form template data.
	 *
	 * @return int|null ID of the new form.
	 *
	 * @todo Fix form email attribute.
	 */
	public function create_form( $data ) {
		if ( empty( $data['title'] ) || empty( $data['fields'] ) ) {
			return;
		}

		$form  = $this->fields_to_form( $data['fields'] );
		$email = $this->form_email( $data['title'], $data['fields'] );

		$contact_form = wpcf7_save_contact_form(
			array(
				'title'  => $data['title'],
				'locale' => get_locale(),
				'form'   => $form,
				'mail'   => $email,
			)
		);

		if ( ! $contact_form ) {
			return;
		}

		return $contact_form->id();
	}

	/**
	 * Removes a form by ID.
	 *
	 * @param integer $form_id Form ID.
	 *
	 * @return boolean Removal result.
	 */
	public function remove_form( $form_id ) {
		$result = wp_delete_post( $form_id );
		return (bool) $result;
	}

	/**
	 * Retrives the current submission ID.
	 *
	 * @return string|null
	 */
	public function submission_id() {
		$submission = $this->submission( true );
		if ( $submission ) {
			return $submission->get_posted_data_hash();
		}
	}

	/**
	 * Retrives the current submission data.
	 *
	 * @param boolean $raw Control if the submission is serialized before exit.
	 *
	 * @return WPCF7_Submission|array Submission data.
	 */
	public function submission( $raw = false ) {
		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return null;
		} elseif ( $raw ) {
			return $submission;
		}

		$form = $this->form();
		return $this->serialize_submission( $submission, $form );
	}

	/**
	 * Retrives the current submission uploaded files.
	 *
	 * @return array Uploaded files data.
	 */
	public function uploads() {
		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return null;
		}

		return $this->submission_uploads( $submission );
	}

	/**
	 * Serializes a contact form instance as array data.
	 *
	 * @param WPCF7_ContactForm $form Form instance.
	 *
	 * @return array.
	 */
	public function serialize_form( $form ) {
		$form_id = (int) $form->id();
		$fields  = array_filter(
			array_map(
				function ( $field ) {
					return $this->serialize_field( $field );
				},
				$form->scan_form_tags()
			)
		);

		return apply_filters(
			'forms_bridge_form_data',
			array(
				'_id'     => 'wpcf7:' . $form_id,
				'id'      => $form_id,
				'title'   => $form->title(),
				'bridges' => FBAPI::get_form_bridges( $form_id, 'wpcf7' ),
				'fields'  => array_values( $fields ),
			),
			$form,
			'wpcf7'
		);
	}

	/**
	 * Serializes a form tags as array data.
	 *
	 * @param WPCF7_FormTag $field Form tag instance.
	 *
	 * @return array.
	 */
	private function serialize_field( $field ) {
		if ( in_array( $field->basetype, array( 'response', 'submit', 'quiz' ), true ) ) {
			return;
		}

		$basetype = $field->basetype;
		$type     = $basetype;

		if ( 'conditional' === $basetype ) {
			$type = $field->get_option( 'type' )[0];
		}

		switch ( $basetype ) {
			case 'radio':
			case 'checkbox':
				$type = 'select';
				break;
			case 'acceptance':
				$type = 'checkbox';
				break;
			case 'hidden':
				$type = 'text';
				break;
		}

		$options = array();
		if ( is_array( $field->values ) ) {
			$values = $field->pipes->collect_afters();

			$l = count( $field->raw_values );
			for ( $i = 0; $i < $l; $i++ ) {
				$options[] = array(
					'value' => $values[ $i ],
					'label' => $field->labels[ $i ],
				);
			}
		}

		$format = 'date' === $type ? 'yyyy-mm-dd' : '';

		return apply_filters(
			'forms_bridge_form_field_data',
			array(
				'id'          => $field->get_id_option(),
				'type'        => $type,
				'name'        => $field->raw_name,
				'label'       => $field->name,
				'required'    => $field->is_required(),
				'options'     => $options,
				'is_file'     => 'file' === $type,
				'is_multi'    => $this->is_multi_field( $field ),
				'conditional' => in_array( $field->basetype, array( 'conditional', 'fileconditional' ), true ),
				'format'      => $format,
				'schema'      => $this->field_value_schema( $field ),
				'basetype'    => $basetype,
			),
			$field,
			'wpcf7'
		);
	}

	/**
	 * Checks if a filed is multi value field.
	 *
	 * @param WPCF7_FormTag $tag Target tag instance.
	 *
	 * @return bool
	 */
	private function is_multi_field( $tag ) {
		$type = str_replace( '*', '', $tag->type );

		if ( 'checkbox' === $type ) {
			return ! $tag->has_option( 'exclusive' );
		}

		if ( 'select' === $type ) {
			return $tag->has_option( 'multiple' );
		}

		return false;
	}

	/**
	 * Gets the field value JSON schema.
	 *
	 * @param WPCF7_FormTag $tag Tag instance.
	 *
	 * @return array JSON schema of the value of the field.
	 */
	private function field_value_schema( $tag ) {
		$type = str_replace( '*', '', $tag->type );

		switch ( $type ) {
			case 'text':
			case 'textarea':
			case 'date':
			case 'email':
			case 'url':
			case 'quiz':
			case 'radio':
				return array( 'type' => 'string' );
			case 'select':
				if ( $tag->has_option( 'multiple' ) ) {
					$items = array();

					$l = count( $tag->values );
					for ( $i = 0; $i < $l; $i++ ) {
						$items[] = array( 'type' => 'string' );
					}

					return array(
						'type'            => 'array',
						'items'           => $items,
						'additionalItems' => false,
						'minItems'        => $tag->is_required() ? 1 : 0,
						'maxItems'        => count( $tag->values ),
					);
				}

				return array( 'type' => 'string' );
			case 'checkbox':
				if ( $tag->has_option( 'exclusive' ) ) {
					return array( 'type' => 'string' );
				}

				$items = array();

				$l = count( $tag->values );
				for ( $i = 0; $i < $l; $i++ ) {
					$items[] = array( 'type' => 'string' );
				}

				return array(
					'type'            => 'array',
					'items'           => $items,
					'additionalItems' => false,
					'minItems'        => $tag->is_required() ? 1 : 0,
					'maxItems'        => count( $tag->values ),
				);
			case 'file':
			case 'files':
				return;
			case 'acceptance':
				return array( 'type' => 'boolean' );
			case 'number':
				return array( 'type' => 'number' );
			default:
				return array( 'type' => 'string' );
		}
	}

	/**
	 * Serializes the form's submission data.
	 *
	 * @param WPCF7_Submission $submission Submission instance.
	 * @param array            $form_data Form data.
	 *
	 * @return array Submission data.
	 */
	public function serialize_submission( $submission, $form_data ) {
		$data = $submission->get_posted_data();

		foreach ( $data as $key => $val ) {
			$index = array_search( $key, array_column( $form_data['fields'], 'name' ), true );
			$field = $form_data['fields'][ $index ];

			if ( is_array( $val ) && ! $field['is_multi'] ) {
				$data[ $key ] = $val[0];
				$val          = $data[ $key ];
			}

			if ( 'hidden' === $field['basetype'] ) {
				$number_val = (float) $val;
				if ( strval( $number_val ) === $val ) {
					$data[ $key ] = $number_val;
				} else {
					$data[ $key ] = $val;
				}
			} elseif ( 'number' === $field['basetype'] ) {
				$data[ $key ] = (float) $val;
			} elseif ( 'file' === $field['basetype'] ) {
				unset( $data[ $key ] );
			} elseif ( 'acceptance' === $field['basetype'] ) {
				$data[ $key ] = (bool) $val;
			}
		}

		return $data;
	}

	/**
	 * Gets submission uploaded files.
	 *
	 * @param WPCF7_Submission $submission Submission instance.
	 *
	 * @return array Uploaded files data.
	 */
	protected function submission_uploads( $submission ) {
		$uploads = array();
		$uploads = $submission->uploaded_files();
		foreach ( $uploads as $file_name => $paths ) {
			if ( ! empty( $paths ) ) {
				$is_multi = count( $paths ) > 1;

				$uploads[ $file_name ] = array(
					'path'     => $is_multi ? $paths : $paths[0],
					'is_multi' => $is_multi,
				);
			} else {
				unset( $uploads[ $file_name ] );
			}
		}

		return $uploads;
	}

	/**
	 * Gets form fields from a template and return a contact form content string.
	 *
	 * @param array $fields Form data fields array.
	 *
	 * @return string Form content.
	 */
	private function fields_to_form( $fields ) {
		$form = '';
		foreach ( $fields as $field ) {
			if ( 'hidden' === $field['type'] ) {
				if ( isset( $field['value'] ) ) {
					if ( is_bool( $field['value'] ) ) {
						$field['value'] = $field['value'] ? '1' : '0';
					}

					$form .= $this->field_to_tag( $field ) . "\n\n";
				}
			} else {
				$form .= "<label> {$field['label']}\n";
				$form .= '  ' . $this->field_to_tag( $field ) . " </label>\n\n";
			}
		}

		$form .= sprintf( '[submit "%s"]', __( 'Submit', 'forms-bridge' ) );
		return $form;
	}

	/**
	 * Gets a field template data and returns a form tag string.
	 *
	 * @param array $field Field data.
	 *
	 * @return string.
	 */
	private function field_to_tag( $field ) {
		if ( isset( $field['value'] ) ) {
			$type = 'hidden';
		} else {
			$type = sanitize_text_field( $field['type'] );

			switch ( $type ) {
				case 'checkbox':
					$type = 'acceptance';
					break;
				case 'select':
					if ( $field['is_multi'] ?? false ) {
						$type = 'checkbox';
					}

					break;
			}

			if ( ( $field['required'] ?? false ) && 'hidden' !== $type ) {
				$type .= '*';
			}
		}

		$name = sanitize_text_field( $field['name'] );
		$tag  = "[{$type} {$name} ";

		foreach ( $field as $key => $val ) {
			if (
				$val &&
				! in_array(
					$key,
					array(
						'name',
						'type',
						'value',
						'required',
						'label',
						'is_multi',
						'is_file',
						'conditional',
						'options',
					),
					true,
				)
			) {
				$key  = sanitize_text_field( $key );
				$val  = trim( sanitize_text_field( $val ) );
				$tag .= "{$key}:{$val} ";
			}
		}

		$value = null;

		if ( strstr( $type, 'select' ) !== false || strstr( $type, 'checkbox' ) !== false ) {
			$options = array();
			foreach ( (array) $field['options'] as $opt ) {
				$value     = $opt['value'];
				$label     = $opt['label'] ?? $value;
				$options[] = $label . '|' . $value;
			}

			$value = implode( '" "', $options );
		} elseif ( ! empty( $field['value'] ) ) {
			$value = sanitize_text_field( (string) $field['value'] );
		}

		if ( $value ) {
			$tag .= "\"{$value}\"";
		}

		return $tag . ']';
	}

	/**
	 * Serialize the email data of a contact form.
	 *
	 * @param string $title Form title.
	 * @param array  $fields Form fields.
	 */
	private function form_email( $title, $fields ) {
		$site_url = get_option( 'siteurl' );
		$host     = wp_parse_url( $site_url )['host'] ?? 'example.coop';

		$email_index = array_search( 'email', array_column( $fields, 'type' ), true );
		if ( $email_index ) {
			$replay_to = 'Replay-To: [' . $fields[ $email_index ]['name'] . ']';
		} else {
			$replay_to = '';
		}

		$body = __(
			"This are the responses to the contact form:\n\n",
			'forms-bridge'
		);

		foreach ( $fields as $field ) {
			$label = $field['label'] ?? $field['name'];
			$body .= ' * ' . esc_html( $label ) . ': [' . $field['name'] . "]\n";
		}

		$notice = sprintf(
			/* translators: 1: blog name, 2: blog URL */
			__(
				'This is a notification that a contact form was submitted on your website (%1$s %2$s).',
				'forms-bridge'
			),
			'[_site_title]',
			'[_site_url]'
		);

		$body .= "\n---\n{$notice}";

		return array(
			'recipient'          => '[_site_admin_email]',
			'sender'             => "[_site_title] <admin@{$host}>",
			'subject'            => "[_site_title] \"{$title}\"",
			'additional_headers' => $replay_to,
			'body'               => $body,
			'attachments'        => '',
		);
	}
}

WPCF7_Integration::setup();
