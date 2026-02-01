<?php
/**
 * Class Form_Bridge_Template
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use Exception;
use Error;
use FBAPI;
use WP_Error;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form Bridge template class. Handles the data validation
 * and the use of template as form bridge creation strategy.
 */
class Form_Bridge_Template {

	/**
	 * Handles the template post type name.
	 *
	 * @var string
	 */
	public const TYPE = 'fb-bridge-template';

	/**
	 * Handles the template id;
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Handles the template addon name.
	 *
	 * @var string
	 */
	protected $addon;

	/**
	 * Handles the template data.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Handles the common template data json schema. The schema is common for all
	 * Form_Bridge_Templates.
	 *
	 * @param string $addon Addon name.
	 *
	 * @return array
	 */
	public static function schema( $addon = null ) {
		$backend_schema    = FBAPI::get_backend_schema();
		$bridge_schema     = FBAPI::get_bridge_schema( $addon );
		$credential_schema = FBAPI::get_credential_schema( $addon );

		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'form-bridge-template',
			'type'                 => 'object',
			'properties'           => array(
				'name'         => array(
					'title'       => _x( 'Name', 'Bridge template schema', 'forms-bridge' ),
					'description' => __( 'Internal and unique name of the template', 'forms-bridge' ),
					'type'        => 'string',
					'minLength'   => 1,
				),
				'title'        => array(
					'title'       => _x( 'Title', 'Bridge template schema', 'forms-bridge' ),
					'description' => __( 'Public title of the template', 'forms-bridge' ),
					'type'        => 'string',
					'minLength'   => 1,
				),
				'description'  => array(
					'title'       => _x( 'Description', 'Bridge template schema', 'forms-bridge' ),
					'description' => __( 'Short description of the template purpose', 'forms-bridge' ),
					'type'        => 'string',
					'default'     => '',
				),
				'integrations' => array(
					'title'       => _x( 'Integrations', 'Bridge template schema', 'forms-bridge' ),
					'description' => __( 'Template\'s supported integrations', 'forms-bridge' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'uniqueItems' => true,
					'minItems'    => 1,
				),
				'fields'       => array(
					'title'       => _x( 'Fields', 'Bridge template schema', 'forms-bridge' ),
					'description' => __( 'Template fields to be filled by the user', 'forms-bridge' ),
					'type'        => 'array',
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'ref'         => array(
								'type'    => 'string',
								'pattern' => '^#.+',
							),
							'name'        => array(
								'type'      => 'string',
								'minLength' => 1,
							),
							'label'       => array(
								'type'      => 'string',
								'minLength' => 1,
							),
							'description' => array( 'type' => 'string' ),
							'type'        => array(
								'type' => 'string',
								'enum' => array(
									'text',
									'number',
									'select',
									'boolean',
									'email',
									'url',
								),
							),
							'required'    => array( 'type' => 'boolean' ),
							'value'       => array(
								'type' => array(
									'integer',
									'number',
									'string',
									'array',
									'boolean',
								),
							),
							'default'     => array(
								'type' => array(
									'integer',
									'number',
									'string',
									'array',
									'boolean',
								),
							),
							'options'     => array(
								'anyOf' => array(
									array(
										'description' => __( 'List of field options', 'forms-bridge' ),
										'type'        => 'array',
										'items'       => array(
											'type'       => 'object',
											'properties' => array(
												'label' => array( 'type' => 'string' ),
												'value' => array( 'type' => 'string' ),
											),
											'required'   => array( 'value', 'label' ),
										),
										'uniqueItems' => true,
									),
									array(
										'description' => __( 'How to get options from the addon API', 'forms-bridge' ),
										'type'        => 'object',
										'properties'  => array(
											'endpoint' => array(
												'description' => __( 'Endpoint to get values from', 'forms-bridge' ),
												'type' => 'string',
											),
											'finger'   => array(
												'description' => __( 'Fingers to get values from the endpoint response', 'forms-bridge' ),
												'oneOf' => array(
													array(
														'type' => 'object',
														'properties' => array(
															'value' => array( 'type' => 'string' ),
															'label' => array( 'type' => 'string' ),
														),
														'required' => array( 'value' ),
													),
													array( 'type' => 'string' ),
												),
											),
										),
										'required'    => array( 'endpoint', 'finger' ),
									),
								),
							),
							'enum'        => array(
								'type'        => 'array',
								'items'       => array( 'type' => array( 'integer', 'number', 'string' ) ),
								'uniqueItems' => true,
							),
							'min'         => array( 'type' => 'integer' ),
							'max'         => array( 'type' => 'integer' ),
							'multiple'    => array( 'type' => 'boolean' ),
							'pattern'     => array( 'type' => 'string' ),
						),
						'required'             => array( 'ref', 'name', 'type' ),
						'additionalProperties' => true,
					),
				),
				'form'         => array(
					'title'                => _x( 'Form', 'Bridge template schema', 'forms-bridge' ),
					'description'          => __( 'Form title and fields settings', 'forms-bridge' ),
					'type'                 => 'object',
					'properties'           => array(
						'title'  => array(
							'type'    => 'string',
							'default' => '',
						),
						'fields' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'        => array(
										'type'      => 'string',
										'minLength' => 1,
									),
									'label'       => array( 'type' => 'string' ),
									'type'        => array(
										'type' => 'string',
										'enum' => array(
											'text',
											'textarea',
											'number',
											'url',
											'email',
											'tel',
											'select',
											'date',
											'hidden',
											'file',
											'checkbox',
										),
									),
									'required'    => array( 'type' => 'boolean' ),
									'options'     => array(
										'type'  => 'array',
										'items' => array(
											'type'       => 'object',
											'properties' => array(
												'value' => array(
													'type' => array(
														'integer',
														'number',
														'string',
														'boolean',
													),
												),
												'label' => array( 'type' => 'string' ),
											),
											'required'   => array( 'value', 'label' ),
										),
									),
									'value'       => array(
										'type' => array(
											'integer',
											'number',
											'string',
											// 'boolean',
											// 'object',
											'array',
											// 'null',
										),
									),
									'conditional' => array( 'type' => 'boolean' ),
									'is_file'     => array( 'type' => 'boolean' ),
									'is_multi'    => array( 'type' => 'boolean' ),
									'filetypes'   => array( 'type' => 'string' ),
									'min'         => array( 'type' => 'number' ),
									'max'         => array( 'type' => 'number' ),
									'step'        => array( 'type' => 'number' ),
									'format'      => array( 'type' => 'string' ),
								),
								'required'   => array( 'name', 'type' ),
							),
						),
					),
					'required'             => array( 'title', 'fields' ),
					'additionalProperties' => false,
				),
				'bridge'       => self::child_schema_to_template(
					$bridge_schema,
					_x( 'Bridge', 'Bridge template schema', 'forms-bridge' )
				),
				'backend'      => self::child_schema_to_template(
					$backend_schema,
					_x( 'Backend', 'Bridge template schema', 'forms-bridge' )
				),
				'credential'   => self::child_schema_to_template(
					$credential_schema,
					_x( 'Credential', 'Bridge template schema', 'forms-bridge' )
				),
			),
			'additionalProperties' => false,
			'required'             => array(
				'name',
				'title',
				'integrations',
				'fields',
				'form',
				'backend',
				'bridge',
			),
		);

		if ( ! $addon ) {
			return $schema;
		}

		return apply_filters( 'forms_bridge_template_schema', $schema, $addon );
	}


	/**
	 * Decorates schemas of the template child objects (form, backend, credential) to scale down
	 * schema requirements and to fit the templates schema.
	 *
	 * @param array  $schema Object json schema.
	 * @param string $title Schema title.
	 *
	 * @return array Decorated schema.
	 */
	private static function child_schema_to_template( $schema, $title ) {
		if ( isset( $schema['oneOf'] ) ) {
			$schema['oneOf'] = array_map(
				static function ( $schema ) use ( $title ) {
					$title = $schema['title'] ?? $title;
					return self::child_schema_to_template( $schema, $title );
				},
				$schema['oneOf']
			);

			return $schema;
		} elseif ( isset( $schema['anyOf'] ) ) {
			$schema['anyOf'] = array_map(
				static function ( $schema ) use ( $title ) {
					$title = $schema['title'] ?? $title;
					return self::child_schema_to_template( $schema, $title );
				},
				$schema['anyOf']
			);

			return $schema;
		}

		foreach ( $schema['properties'] as &$prop_schema ) {
			if ( 'string' === $prop_schema['type'] ) {
				$prop_schema['default'] = '';
				unset( $prop_schema['minLength'] );
				unset( $prop_schema['pattern'] );
				unset( $prop_schema['format'] );
			} elseif ( 'array' === $prop_schema['type'] ) {
				$prop_schema['default'] = array();
				unset( $prop_schema['minItems'] );
			}
		}

		if ( ! isset( $schema['default'] ) ) {
			$schema['default'] = array();
		}

		$schema['title'] = $title;
		return $schema;
	}

	/**
	 * Template default data getter.
	 *
	 * @param string $addon Template addon namespace.
	 * @param array  $schema Template schema.
	 *
	 * @return array
	 */
	protected static function defaults( $addon = null, $schema = null ) {
		if ( ! is_array( $schema ) ) {
			$schema = static::schema( $addon );
		}

		return apply_filters(
			'forms_bridge_template_defaults',
			array(
				'integrations' => array(),
				'fields'       => array(
					array(
						'ref'   => '#form',
						'name'  => 'id',
						'label' => __( 'Form ID', 'forms-bridge' ),
						'type'  => 'text',
					),
					array(
						'ref'      => '#form',
						'name'     => 'title',
						'label'    => __( 'Form title', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'      => '#backend',
						'name'     => 'name',
						'label'    => __( 'Name', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'      => '#backend',
						'name'     => 'base_url',
						'label'    => __( 'Base URL', 'forms-bridge' ),
						'type'     => 'url',
						'required' => true,
						'default'  => 'https://',
						'format'   => 'uri',
					),
					array(
						'ref'       => '#bridge',
						'name'      => 'name',
						'label'     => __( 'Bridge name', 'forms-bridge' ),
						'type'      => 'text',
						'required'  => true,
						'minLength' => 1,
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'endpoint',
						'label'    => __( 'Endpoint', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
						'default'  => '',
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'method',
						'label'    => __( 'Method', 'forms-bridge' ),
						'type'     => 'options',
						'options'  => array(
							array(
								'label' => 'GET',
								'value' => 'GET',
							),
							array(
								'label' => 'POST',
								'value' => 'POST',
							),
							array(
								'label' => 'PUT',
								'value' => 'PUT',
							),
							array(
								'label' => 'PATCH',
								'value' => 'PATCH',
							),
							array(
								'label' => 'DELETE',
								'value' => 'DELETE',
							),
						),
						'required' => true,
						'default'  => 'POST',
					),
				),
				'bridge'       => array(
					'endpoint' => '',
					'method'   => 'POST',
				),
				'backend'      => array(
					'headers' => array(
						array(
							'name'  => 'Content-Type',
							'value' => 'application/json',
						),
					),
				),
				'form'         => array(
					'title'  => '',
					'fields' => array(),
				),
			),
			$addon,
			$schema
		);
	}

	/**
	 * Gets the template config data from a post.
	 *
	 * @param WP_Post $post Post instance.
	 *
	 * @return array Template config data.
	 */
	private static function data_from_post( $post ) {
		return array(
			'name'        => $post->post_name,
			'title'       => $post->post_title,
			'description' => $post->post_excerpt,
			'fields'      => (array) ( get_post_meta( $post->ID, '_template-fields', true ) ?: array() ),
			'form'        => (array) ( get_post_meta( $post->ID, '_template-form', true ) ?: array() ),
			'bridge'      => (array) ( get_post_meta( $post->ID, '_template-bridge', true ) ?: array() ),
			'backend'     => (array) ( get_post_meta( $post->ID, '_template-backend', true ) ?: array() ),
			'credential'  => (array) ( get_post_meta( $post->ID, '_template-credential', true ) ?: array() ),
		);
	}

	/**
	 * Store template attribute values, validates data and binds the
	 * instance to custom forms bridge template hooks.
	 *
	 * @param array  $data Template data.
	 * @param string $addon Addon name.
	 */
	public function __construct( $data, $addon ) {
		if ( $data instanceof WP_Post ) {
			$data = self::data_from_post( $data );
		}

		$this->addon = $addon;
		$this->data  = $this->validate( $data );

		if ( $this->is_valid ) {
			$this->id = $this->addon . '-' . $data['name'];
		}
	}

	/**
	 * Magic method to proxy private template attributes and data.
	 *
	 * @param string $name Attribute name.
	 *
	 * @return mixed Attribute value or null.
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'id':
				return $this->id;
			case 'addon':
				return $this->addon;
			case 'data':
				return $this->data;
			case 'is_valid':
				return ! is_wp_error( $this->data ) &&
					Addon::addon( $this->addon ) !== null;
			default:
				if ( ! $this->is_valid ) {
					return;
				}

				return $this->data[ $name ] ?? null;
		}
	}

	/**
	 * Validates input data against the template schema.
	 *
	 * @param array $data Input data.
	 *
	 * @return array|WP_Error Validated data.
	 */
	private function validate( $data ) {
		$schema   = static::schema( $this->addon );
		$defaults = static::defaults( $this->addon, $schema );

		if ( empty( $data['integrations'] ) ) {
			foreach ( Integration::integrations() as $integration ) {
				if ( 'woo' !== $integration::NAME ) {
					$data['integrations'][] = $integration::NAME;
				}
			}
		}

		$data = wpct_plugin_merge_object( $data, $defaults, $schema );
		return wpct_plugin_sanitize_with_schema( $data, $schema );
	}

	/**
	 * Decorates the template data for REST responses.
	 *
	 * @return array REST data.
	 */
	public function data() {
		if ( ! $this->is_valid ) {
			return;
		}

		return array_merge(
			array(
				'id'    => $this->id,
				'addon' => $this->addon,
			),
			$this->data
		);
	}

	/**
	 * Gets the templates's post ID from database.
	 *
	 * @return int|null Int if it's stored on the database, null otherwise.
	 */
	private function get_post_id() {
		// phpcs:disable WordPress.DB.SlowDBQuery
		$ids = get_posts(
			array(
				'post_type'              => self::TYPE,
				'name'                   => $this->name,
				'meta_key'               => '_fb-addon',
				'meta_value'             => $this->addon,
				'fields'                 => 'ids',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'update_menu_item_cache' => false,
			)
		);
		// phpcs:enable

		if ( count( $ids ) ) {
			return $ids[0];
		}
	}

	/**
	 * Save the template config to the database as a custom post entry.
	 *
	 * @return int|WP_Error Post ID or WP_Error in case of insert errors.
	 */
	public function save() {
		if ( ! $this->is_valid ) {
			return $this->data;
		}

		$post_arr = array(
			'post_type'    => self::TYPE,
			'post_name'    => $this->name,
			'post_title'   => $this->title,
			'post_excerpt' => $this->description,
		);

		$post_id = $this->get_post_id();
		if ( $post_id ) {
			$post_arr['ID'] = $post_id;
			$post_id        = wp_update_post( $post_arr, true );
		} else {
			$post_id = wp_insert_post( $post_arr, true );
		}

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_template-fields', $this->fields );
			update_post_meta( $post_id, '_template-form', $this->form );
			update_post_meta( $post_id, '_template-bridge', $this->bridge );
			update_post_meta( $post_id, '_template-backend', $this->backend );
			update_post_meta( $post_id, '_template-credential', $this->credential );
		}

		return $post_id;
	}

	/**
	 * Delete the template config from the database and restore its default configuration.
	 * If the template does not exists as file-based configuration, the template will be deleted.
	 *
	 * @return bool
	 */
	public function reset() {
		$post_id = $this->get_post_id();

		if ( ! $post_id ) {
			return false;
		}

		return wp_delete_post( $post_id, true ) instanceof WP_Post;
	}

	/**
	 * Applies the input fields with the template's data to
	 * create a form and bind it with a bridge.
	 *
	 * @param array  $fields User input fields data.
	 * @param string $integration Target integration.
	 */
	public function use( $fields, $integration ) {
		if ( ! $this->is_valid ) {
			return new WP_Error(
				'invalid_template',
				__( 'The target template is invalid', 'forms-bridge' )
			);
		}

		$template = $this->data;
		$schema   = static::schema( $this->addon );

		// Add constants to the user fields.
		foreach ( $template['fields'] as $field ) {
			if ( ! empty( $field['value'] ) ) {
				$fields[] = $field;
			}
		}

		$all_fields = wpct_plugin_merge_collection(
			$fields,
			$template['fields'],
			$schema['properties']['fields']['items']
		);

		$requireds = array_filter(
			$all_fields,
			static function ( $field ) {
				return ( $field['required'] ?? false ) && ! isset( $field['value'] );
			}
		);

		if ( count( $requireds ) || count( $fields ) > count( $all_fields ) ) {
			return new WP_Error(
				'invalid_fields',
				__( 'Invalid template fields', 'forms-bridge' )
			);
		}

		$data = $template;
		foreach ( $fields as $field ) {
			$is_required = $field['required'] ?? false;

			$field_schema             = $schema['properties']['fields']['items'];
			$field_schema['required'] = array( 'ref', 'name' );

			if ( $is_required ) {
				$field_schema['required'][] = 'value';
			}

			$field = wpct_plugin_sanitize_with_schema( $field, $field_schema );

			if ( is_wp_error( $field ) ) {
				return new WP_Error(
					'invalid_field',
					sprintf(
						/* translators: %s: Field name */
						__( 'Field `%s` does not match the schema', 'forms-bridge' ),
						$field['name']
					)
				);
			}

			if ( ! isset( $field['value'] ) ) {
				continue;
			}

			if ( is_array( $field['value'] ) && empty( $field['type'] ) ) {
				continue;
			}

			if ( '' === $field['value'] ) {
				continue;
			}

			if ( 'boolean' === $field['type'] ) {
				if ( ! isset( $field['value'][0] ) ) {
					continue;
				} else {
					$field['value'] = '1';
				}
			}

			// Inherit form field structure if field ref points to form fields.
			if ( '#form/fields[]' === $field['ref'] ) {
				$index = array_search(
					$field['name'],
					array_column( $template['form']['fields'], 'name' ),
					true
				);

				$form_field     = $template['form']['fields'][ $index ];
				$field['index'] = $index;
				$field['value'] = array_merge(
					$form_field,
					array(
						'value' => $field['value'],
					)
				);
			} elseif (
				'#backend/headers[]' === $field['ref'] ||
				'#bridge/custom_fields[]' === $field['ref']
			) {
				$field['value'] = array(
					'name'  => $field['name'],
					'value' => $field['value'] ?? null,
				);
			}

			$keys = explode( '/', substr( $field['ref'], 1 ) );
			$leaf = &$data;
			foreach ( $keys as $key ) {
				$clean_key = str_replace( '[]', '', $key );
				if ( ! isset( $leaf[ $clean_key ] ) ) {
					return new WP_Error(
						'invalid_ref',
						sprintf(
							/* translators: %s: ref value */
							__( 'Invalid template field ref `%s`', 'forms-bridge' ),
							$field['ref']
						)
					);
				}

				$leaf = &$leaf[ $clean_key ];
			}

			if ( '[]' === substr( $key, -2 ) ) {
				if ( isset( $field['index'] ) ) {
					$leaf[ $field['index'] ] = $field['value'];
				} else {
					$leaf[] = $field['value'];
				}
			} elseif ( isset( $field['value'] ) ) {
				$leaf[ $field['name'] ] = $field['value'];
			}
		}

		$data = apply_filters( 'forms_bridge_template_data', $data, $this->id, $this );

		if ( is_wp_error( $data ) ) {
			return $data;
		} elseif ( empty( $data ) ) {
			return new WP_Error(
				'template_creation_error',
				__( 'There is a problem with the template data', 'forms-bridge' )
			);
		}

		if ( 'woo' === $integration ) {
			$data['form']['id'] = 1;
		} elseif ( in_array( $integration, array( 'wpforms', 'formidable' ), true ) ) {
			$mappers = array();
			foreach ( $data['form']['fields'] as &$field ) {
				if ( 'file' !== $field['type'] ) {
					$mappers[] = array(
						'from' => JSON_Finger::sanitize_key( $field['label'] ),
						'to'   => $field['name'],
						'cast' => 'inherit',
					);
				}

				$field['name'] = $field['label'];
			}

			$data['bridge']['mutations'][0] = array_merge( $mappers, $data['bridge']['mutations'][0] ?? array() );
		}

		$integration_instance = Integration::integration( $integration );

		try {
			$create_form = ! $this->form_exists(
				$data['form']['id'] ?? null,
				$integration
			);

			if ( $create_form ) {
				do_action_ref_array(
					'forms_bridge_before_template_form',
					array( $data['form'], $this->name, $this ),
				);

				$form_id = $integration_instance->create_form( $data['form'] );

				if ( ! $form_id ) {
					return new WP_Error(
						'form_creation_error',
						__( 'Forms bridge can\'t create the form', 'forms-bridge' ),
						array(
							'status' => 400,
							'data'   => $data['form'],
						)
					);
				}

				$data['form']['id'] = $form_id;

				do_action( 'forms_bridge_template_form', $data['form'], $this->id, $this );
			}

			$data['bridge']['form_id'] = $integration . ':' . $data['form']['id'];

			$create_credential = false;
			if ( ! empty( $data['credential']['name'] ) ) {
				$create_credential = ! $this->credential_exists( $data['credential']['name'] );

				if ( $create_credential ) {
					$result = $this->create_credential( $data['credential'] );

					if ( ! $result ) {
						if ( $create_form ) {
							$integration_instance->remove_form( $data['form']['id'] );
						}

						return new WP_Error(
							'credential_creation_error',
							__( 'Forms bridge can\'t create the credential', 'forms-bridge' ),
							array(
								'status' => 400,
								'data'   => $data['credential'],
							)
						);
					}
				}

				$data['backend']['credential'] = $data['credential']['name'];
			}

			$create_backend = ! $this->backend_exists( $data['backend']['name'] );
			if ( $create_backend ) {
				$result = $this->create_backend( $data['backend'] );

				if ( ! $result ) {
					if ( $create_form ) {
						$integration_instance->remove_form( $data['form']['id'] );
					}

					if ( $create_credential ) {
						$this->remove_credential( $data['credential']['name'] );
					}

					return new WP_Error(
						'backend_creation_error',
						__( 'Forms bridge can\'t create the backend', 'forms-bridge' ),
						array(
							'status' => 400,
							'data'   => $data['backend'],
						)
					);
				}
			}

			$data['bridge']['backend'] = $data['backend']['name'];

			$bridge_created = $this->create_bridge( $data['bridge'] );

			if ( ! $bridge_created ) {
				if ( $create_form ) {
					$integration_instance->remove_form( $data['form']['id'] );
				}

				if ( $create_credential ) {
					$this->remove_credential( $data['credential']['name'] );
				}

				if ( $create_backend ) {
					$this->remove_backend( $data['backend']['name'] );
				}

				return new WP_Error(
					'bridge_creation_error',
					__( 'Forms bridge can\'t create the form bridge', 'forms-bridge' ),
					array(
						'status' => 400,
						'data'   => $data['bridge'],
					)
				);
			}
		} catch ( Error | Exception $e ) {
			if ( isset( $create_form ) && $create_form ) {
				$integration_instance->remove_form( $data['form']['id'] );
			}

			if ( isset( $create_credential ) && $create_credential ) {
				$this->remove_credential( $data['credential']['name'] );
			}

			if ( isset( $create_backend ) && $create_backend ) {
				$this->remove_backend( $data['backend']['name'] );
			}

			if ( isset( $bridge_created ) && $bridge_created ) {
				$this->remove_bridge( $data['bridge']['name'] );
			}

			return new WP_Error(
				'internal_server_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Checks if a form with the given id exists on the settings store.
	 *
	 * @param string $form_id Internal ID of the form.
	 * @param string $integration Slug of the target integration.
	 *
	 * @return boolean
	 */
	private function form_exists( $form_id, $integration ) {
		$form = FBAPI::get_form_by_id( $form_id, $integration );
		return ! empty( $form['id'] );
	}

	/**
	 * Checks if a backend with the given name exists on the settings store.
	 *
	 * @param string $name Backend name.
	 *
	 * @return boolean
	 */
	final protected function backend_exists( $name ) {
		$backends = Settings_Store::setting( 'http' )->backends ?: array();
		return array_search( $name, array_column( $backends, 'name' ), true ) !== false;
	}

	/**
	 * Stores the backend data on the settings store.
	 *
	 * @param array $data Backend data.
	 *
	 * @return boolean Creation result.
	 */
	private function create_backend( $data ) {
		$setting  = Settings_Store::setting( 'http' );
		$backends = $setting->backends ?: array();

		do_action_ref_array(
			'forms_bridge_before_template_backend',
			array( $data, $this->name, $this ),
		);

		$setting->backends = array_merge( $backends, array( $data ) );
		$setting->flush();

		$is_valid = $this->backend_exists( $data['name'] );
		if ( ! $is_valid ) {
			return;
		}

		do_action( 'forms_bridge_template_backend', $data, $this->id, $this );
		return true;
	}

	/**
	 * Removes backend from the settings store by name.
	 *
	 * @param string $name Backend name.
	 */
	private function remove_backend( $name ) {
		$setting  = Settings_Store::setting( 'http' );
		$backends = $setting->backends ?: array();

		$setting->backends = array_filter(
			$backends,
			static function ( $backend ) use ( $name ) {
				return $backend['name'] !== $name;
			}
		);
	}

	/**
	 * Checks if a bridge with the given name exists on the settings store.
	 *
	 * @param string $name Bridge name.
	 *
	 * @return boolean
	 */
	private function bridge_exists( $name ) {
		$bridges = Settings_Store::setting( $this->addon )->bridges ?: array();
		return false !== array_search( $name, array_column( $bridges, 'name' ), true );
	}

	/**
	 * Stores the form bridge data on the settings store.
	 *
	 * @param array $data Form bridge data.
	 *
	 * @return boolean Creation result.
	 */
	private function create_bridge( $data ) {
		$name_conflict = $this->bridge_exists( $data['name'] );
		if ( $name_conflict ) {
			return;
		}

		$setting = Settings_Store::setting( $this->addon );
		$bridges = $setting->bridges ?: array();

		do_action_ref_array(
			'forms_bridge_before_template_bridge',
			array( $data, $this->name, $this ),
		);

		$setting->bridges = array_merge( $bridges, array( $data ) );
		$setting->flush();

		$is_valid = $this->bridge_exists( $data['name'] );
		if ( ! $is_valid ) {
			return;
		}

		do_action( 'forms_bridge_template_bridge', $data, $this->id, $this );
		return true;
	}

	/**
	 * Removes a bridge from the settings store by name.
	 *
	 * @param string $name Bridge name.
	 */
	private function remove_bridge( $name ) {
		$setting = Settings_Store::setting( $this->addon );
		$bridges = $setting->bridges ?: array();

		$setting->bridges = array_filter(
			$bridges,
			static function (
				$bridge
			) use ( $name ) {
				return $bridge['name'] !== $name;
			}
		);
	}

	/**
	 * Checks if a credential with the given name exists on the settings store.
	 *
	 * @param string $name Credential name.
	 *
	 * @return boolean
	 */
	private function credential_exists( $name ) {
		$credentials = Settings_Store::setting( 'http' )->credentials ?: array();
		return false !== array_search( $name, array_column( $credentials, 'name' ), true );
	}

	/**
	 * Stores the bridge credential data on the settings store.
	 *
	 * @param array $data Credential data.
	 *
	 * @return boolean Creation result.
	 */
	private function create_credential( $data ) {
		$setting     = Settings_Store::setting( 'http' );
		$credentials = $setting->credentials ?: array();

		if ( ! is_array( $credentials ) ) {
			return;
		}

		do_action_ref_array(
			'forms_bridge_before_template_credential',
			array(
				$data,
				$this->name,
				$this,
			)
		);

		$setting->credentials = array_merge( $credentials, array( $data ) );
		$setting->flush();

		$is_valid = $this->credential_exists( $data['name'] );
		if ( ! $is_valid ) {
			return;
		}

		do_action( 'forms_bridge_template_credential', $data, $this->id, $this );
		return true;
	}

	/**
	 * Removes a credential from the settings store by name.
	 *
	 * @param string $name Credential name.
	 */
	private function remove_credential( $name ) {
		$setting     = Settings_Store::setting( 'http' );
		$credentials = $setting->credentials ?: array();

		$setting->credentials = array_filter(
			$credentials,
			static function (
				$credential
			) use ( $name ) {
				return $credential['name'] !== $name;
			}
		);
	}
}
