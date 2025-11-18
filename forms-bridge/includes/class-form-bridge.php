<?php
/**
 * Class Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FBAPI;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge object.
 */
class Form_Bridge {
	/**
	 * Handles the list of available magic tags.
	 *
	 * @var array
	 */
	public const TAGS = array(
		'site_title',
		'site_description',
		'blog_url',
		'site_url',
		'admin_email',
		'wp_version',
		'ip_address',
		'referer',
		'user_agent',
		'browser_locale',
		'locale',
		'language',
		'datetime',
		'gmt_datetime',
		'timestamp',
		'iso_date',
		'gmt_iso_date',
		'utc_date',
		'user_id',
		'user_login',
		'user_name',
		'user_email',
		'submission_id',
		'form_title',
		'form_id',
	);

	/**
	 * Bridge data common schema.
	 *
	 * @param string|null $addon Forwarded to the 'forms_bridge_bridge_schema' filter
	 *                           to allow addon schema updates.
	 *
	 * @return array Bridge json schema.
	 */
	public static function schema( $addon = null ) {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'form-bridge',
			'type'                 => 'object',
			'properties'           => array(
				'name'          => array(
					'title'       => _x( 'Name', 'Bridge schema', 'forms-bridge' ),
					'description' => __(
						'Unique name of the bridge',
						'forms-bridge'
					),
					'type'        => 'string',
					'minLength'   => 1,
				),
				'form_id'       => array(
					'title'       => _x( 'Form', 'Bridge schema', 'forms-bridge' ),
					'description' => __(
						'Internal form id with integration prefix',
						'forms-bridge'
					),
					'type'        => 'string',
					'pattern'     => '^\w+:\d+$',
					'default'     => '',
				),
				'backend'       => array(
					'title'       => _x( 'Backend', 'Bridge schema', 'forms-bridge' ),
					'description' => __( 'Backend name', 'forms-bridge' ),
					'type'        => 'string',
					// 'default' => '',
				),
				'endpoint'      => array(
					'title'       => _x( 'Endpoint', 'Bridge schema', 'forms-bridge' ),
					'description' => __( 'HTTP API endpoint', 'forms-bridge' ),
					'type'        => 'string',
					'default'     => '/',
				),
				'method'        => array(
					'title'       => _x( 'Method', 'Bridge schema', 'forms-bridge' ),
					'description' => __( 'HTTP method', 'forms-bridge' ),
					'type'        => 'string',
					'enum'        => array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ),
					'default'     => 'POST',
				),
				'custom_fields' => array(
					'description' => __(
						'Array of bridge\'s custom fields',
						'forms-bridge'
					),
					'type'        => 'array',
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'  => array(
								'type'              => 'string',
								'minLength'         => 1,
								'validate_callback' =>
									'\FORMS_BRIDGE\JSON_Finger::validate',
							),
							'value' => array(
								'type'      => array( 'string', 'integer', 'number' ),
								'minLength' => 1,
							),
						),
						'additionalProperties' => false,
						'required'             => array( 'name', 'value' ),
					),
					'default'     => array(),
				),
				'mutations'     => array(
					'description' => __(
						'Stack of bridge mutations',
						'forms-bridge'
					),
					'type'        => 'array',
					'items'       => array(
						'type'  => 'array',
						'items' => array(
							'type'                 => 'object',
							'properties'           => array(
								'from' => array(
									'type'              => 'string',
									'minLength'         => 1,
									'validate_callback' =>
										'\FORMS_BRIDGE\JSON_Finger::validate',
								),
								'to'   => array(
									'type'              => 'string',
									'minLength'         => 1,
									'validate_callback' =>
										'\FORMS_BRIDGE\JSON_Finger::validate',
								),
								'cast' => array(
									'type' => 'string',
									'enum' => array(
										'boolean',
										'string',
										'integer',
										'number',
										'not',
										'and',
										'or',
										'xor',
										'json',
										'pretty_json',
										'csv',
										'concat',
										'join',
										'sum',
										'count',
										'inherit',
										'copy',
										'null',
									),
								),
							),
							'additionalProperties' => false,
							'required'             => array( 'from', 'to', 'cast' ),
						),
					),
					'default'     => array(),
				),
				'workflow'      => array(
					'description' => __(
						'Chain of workflow job names',
						'forms-bridge'
					),
					'type'        => 'array',
					'items'       => array(
						'type'      => 'string',
						'minLength' => 1,
					),
					'default'     => array(),
				),
				'is_valid'      => array(
					'description' => __(
						'Validation result of the bridge setting',
						'forms-bridge'
					),
					'type'        => 'boolean',
					'default'     => true,
				),
				'enabled'       => array(
					'description' => __(
						'Boolean flag to enable/disable a bridge',
						'forms-bridge'
					),
					'type'        => 'boolean',
					'default'     => true,
				),
			),
			'required'             => array(
				'name',
				'form_id',
				'backend',
				'method',
				'endpoint',
				'custom_fields',
				'mutations',
				'workflow',
				'is_valid',
				'enabled',
			),
			'additionalProperties' => false,
		);

		if ( ! $addon ) {
			return $schema;
		}

		return apply_filters( 'forms_bridge_bridge_schema', $schema, $addon );
	}

	/**
	 * Handles the form bridge setting data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Handles the form bridge identifier string.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Handles form bridge addon slug.
	 *
	 * @var string
	 */
	protected $addon;

	/**
	 * Stores the form bridge's data as a private attribute.
	 *
	 * @param array  $data Bridge data.
	 * @param string $addon Bridge addon.
	 */
	public function __construct( $data, $addon = 'rest' ) {
		$this->data  = wpct_plugin_sanitize_with_schema(
			$data,
			static::schema( $addon )
		);
		$this->addon = $addon;

		if ( $this->is_valid ) {
			$this->id = $addon . '-' . $data['name'];
		}
	}

	/**
	 * Bridge data getter.
	 *
	 * @return arra|null
	 */
	public function data() {
		if ( ! $this->is_valid ) {
			return;
		}

		return array_merge(
			$this->data,
			array(
				'id'    => $this->id,
				'name'  => $this->name,
				'addon' => $this->addon,
			)
		);
	}

	/**
	 * Magic method to proxy public attributes to method getters.
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
			case 'form':
				return $this->form();
			case 'integration':
				return $this->integration();
			case 'backend':
				return $this->backend();
			case 'content_type':
				return $this->content_type();
			case 'workflow':
				return $this->workflow();
			case 'is_valid':
				return ! is_wp_error( $this->data ) &&
					$this->data['is_valid'] &&
					Addon::addon( $this->addon ) !== null;
			default:
				if ( ! $this->is_valid ) {
					return;
				}

				return $this->data[ $name ] ?? null;
		}
	}

	/**
	 * Retrives the bridge's backend instance.
	 *
	 * @return Backend|null
	 */
	protected function backend() {
		if ( ! $this->is_valid ) {
			return;
		}

		return FBAPI::get_backend( $this->data['backend'] );
	}

	/**
	 * Retrives the bridge's form data.
	 *
	 * @return array|null
	 */
	protected function form() {
		$form_id = $this->form_id;
		if ( ! $form_id ) {
			return;
		}

		if ( ! preg_match( '/^\w+:\d+$/', $form_id ) ) {
			return;
		}

		[$integration, $form_id] = explode( ':', $form_id );
		return FBAPI::get_form_by_id( $form_id, $integration );
	}

	/**
	 * Retrives the bridge's integration name.
	 *
	 * @return string
	 */
	protected function integration() {
		$form_id = $this->form_id;
		if ( ! $form_id ) {
			return;
		}

		if ( ! preg_match( '/^\w+:\d+$/', $form_id ) ) {
			return;
		}

		list($integration) = explode( ':', $form_id );
		return $integration;
	}

	/**
	 * Gets bridge's default body encoding schema.
	 *
	 * @return string|null
	 */
	protected function content_type() {
		if ( ! $this->is_valid ) {
			return;
		}

		$backend = FBAPI::get_backend( $this->data['backend'] );
		if ( ! $backend ) {
			return;
		}

		return $backend->content_type;
	}

	/**
	 * Gets bridge's workflow instance.
	 *
	 * @return Workflow_Job|null;
	 */
	protected function workflow() {
		if ( ! $this->is_valid ) {
			return;
		}

		return Job::from_workflow( $this->data['workflow'], $this->addon );
	}

	/**
	 * Submits payload and attachments to the bridge's backend.
	 *
	 * @param array $payload Payload data.
	 * @param array $attachments Submission's attached files.
	 *
	 * @return array|WP_Error Http request response.
	 */
	public function submit( $payload = array(), $attachments = array() ) {
		if ( ! $this->is_valid ) {
			return new WP_Error(
				'invalid_bridge',
				'Bridge data is invalid',
				(array) $this->data,
			);
		}

		$schema = $this->schema();

		if (
			! in_array(
				$this->method,
				$schema['properties']['method']['enum'],
				true
			)
		) {
			return new WP_Error(
				'method_not_allowed',
				sprintf(
					/* translators: %s: method name */
					__( 'HTTP method %s is not allowed', 'forms-bridge' ),
					sanitize_text_field( $this->method )
				),
				array( 'method' => $this->method )
			);
		}

		$backend = $this->backend();
		if ( ! $backend ) {
			return new WP_Error( 'invalid_bridge' );
		}

		$method = $this->method;

		return $backend->$method( $this->endpoint, $payload, array(), $attachments );
	}

	/**
	 * Apply cast mappers to data.
	 *
	 * @param array      $data Array of data.
	 * @param array|null $mutation Array of mappers.
	 *
	 * @return array Data modified by the bridge's mappers.
	 */
	final public function apply_mutation( $data, $mutation = null ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$finger = new JSON_Finger( $data );

		if ( null === $mutation ) {
			$mutation = $this->mutations[0] ?? array();
		}

		foreach ( $mutation as $mapper ) {
			$is_valid =
				JSON_Finger::validate( $mapper['from'] ) &&
				JSON_Finger::validate( $mapper['to'] );

			if ( ! $is_valid ) {
				continue;
			}

			$isset = $finger->isset( $mapper['from'], $is_conditional );
			if ( ! $isset ) {
				if ( $is_conditional ) {
					continue;
				}

				$value = null;
			} else {
				$value = $finger->get( $mapper['from'] );
			}

			$unset = 'null' === $mapper['cast'];

			if ( 'copy' !== $mapper['cast'] ) {
				$unset =
					$unset ||
					preg_replace( '/^\?/', '', $mapper['from'] ) !==
						$mapper['to'];
			}

			if ( $unset ) {
				$finger->unset( $mapper['from'] );
			}

			if ( 'null' !== $mapper['cast'] ) {
				$finger->set( $mapper['to'], $this->cast( $value, $mapper ) );
			}
		}

		return $finger->data();
	}

	/**
	 * Casts value to the given type.
	 *
	 * @param mixed  $value Original value.
	 * @param string $mapper Source mapper.
	 *
	 * @return mixed
	 */
	private function cast( $value, $mapper ) {
		if ( strpos( $mapper['from'], '[]' ) !== false ) {
			return $this->cast_expanded( $value, $mapper );
		}

		switch ( $mapper['cast'] ) {
			case 'string':
				return (string) $value;
			case 'integer':
				return (int) $value;
			case 'number':
				return (float) $value;
			case 'boolean':
				return (bool) $value;
			case 'not':
				return ! $value;
			case 'and':
				return array_reduce(
					(array) $value,
					fn( $bool, $val ) => $bool && $val,
					! empty( $val )
				);
			case 'or':
				return array_reduce(
					(array) $value,
					fn( $bool, $val ) => $bool || $val,
					false
				);
			case 'xor':
				return array_reduce(
					(array) $value,
					fn( $bool, $val ) => $bool xor $val,
					false
				);
			case 'json':
				if ( is_array( $value ) || is_object( $value ) ) {
					return wp_json_encode( (array) $value, JSON_UNESCAPED_UNICODE );
				}

				return $value;
			case 'pretty_json':
				if ( is_array( $value ) || is_object( $value ) ) {
					return wp_json_encode( (array) $value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
				}

				return $value;
			case 'csv':
				if ( ! wp_is_numeric_array( $value ) ) {
					return '';
				}

				return implode( ',', $value );
			case 'concat':
				if ( ! wp_is_numeric_array( $value ) ) {
					return '';
				}

				return implode( ' ', $value );
			case 'join':
				if ( ! wp_is_numeric_array( $value ) ) {
					return '';
				}

				return implode( '', $value );
			case 'sum':
				if ( ! wp_is_numeric_array( $value ) ) {
					return 0;
				}

				return array_reduce(
					(array) $value,
					static function ( $total, $val ) {
						return $total + $val;
					},
					0
				);
			case 'count':
				if ( ! is_array( $value ) ) {
					return 0;
				}

				return count( (array) $value );
			case 'inherit':
				return $value;
			case 'copy':
				return $value;
			case 'null':
				return;
			default:
				return (string) $value;
		}
	}

	/**
	 * Recursivly apply cast mutations to arrays of values.
	 *
	 * @param array $values Array of values.
	 * @param array $mapper Source mapper.
	 *
	 * @return array
	 */
	private function cast_expanded( $values, $mapper ) {
		if ( ! wp_is_numeric_array( $values ) ) {
			return array();
		}

		$is_expanded =
			strpos( preg_replace( '/\[\]$/', '', $mapper['from'] ), '[]' ) !==
			false;

		if ( ! $is_expanded ) {
			return array_map(
				function ( $value ) use ( $mapper ) {
					return $this->cast(
						$value,
						array(
							'from' => '',
							'to'   => '',
							'cast' => $mapper['cast'],
						)
					);
				},
				$values
			);
		}

		preg_match_all(
			'/\[\](?=[^\[])/',
			preg_replace( '/\[\]$/', '', $mapper['to'] ),
			$to_expansions
		);
		preg_match_all(
			'/\[\](?=[^\[])/',
			preg_replace( '/\[\]$/', '', $mapper['from'] ),
			$from_expansions
		);

		if ( empty( $from_expansions ) && count( $to_expansions ) > 1 ) {
			return array();
		} elseif (
			! empty( $from_expansions ) &&
			count( $to_expansions[0] ) > count( $from_expansions[0] )
		) {
			return array();
		}

		$parts  = array_filter( explode( '[]', $mapper['from'] ) );
		$before = $parts[0];
		$after  = implode( '[]', array_slice( $parts, 1 ) );

		$l = count( $values );
		for ( $i = 0; $i < $l; $i++ ) {
			$pointer      = "{$before}[{$i}]{$after}";
			$values[ $i ] = $this->cast(
				$values[ $i ],
				array(
					'from' => $pointer,
					'to'   => '',
					'cast' => $mapper['cast'],
				)
			);
		}

		return $values;
	}

	/**
	 * Apply modifications to the bridge mutations layers to handle conditional
	 * and multi response form fields.
	 *
	 * @param array $form_data Form data.
	 */
	final public function prepare_mappers( $form_data ) {
		foreach ( $form_data['fields'] as $field ) {
			$is_file        = $field['is_file'] ?? false;
			$is_conditional = $field['conditional'] ?? false;
			$is_multi       = $field['is_multi'] ?? false;

			$schema = $field['schema'] ?? array( 'type' => '-' );

			if (
				'array' === $schema['type'] &&
				false === ( $schema['additionalItems'] ?? true )
			) {
				$min_items = $field['schema']['minItems'] ?? 0;
				$max_items = $field['schema']['maxItems'] ?? 0;

				$is_conditional = $is_conditional || $min_items < $max_items;
			}

			if ( $is_conditional ) {
				$name = $field['name'];

				$l = count( $this->data['mutations'] );
				for ( $i = 0; $i < $l; $i++ ) {
					$mutation = $this->data['mutations'][ $i ];

					$m = count( $mutation );
					for ( $j = 0; $j < $m; $j++ ) {
						$mapper = $this->data['mutations'][ $i ][ $j ];

						$from = preg_replace( '/\[\d*\]/', '', $mapper['from'] );
						if (
							$from === $name ||
							( $is_file && $from === $name . '_filename' )
						) {
							$this->data['mutations'][ $i ][ $j ]['from'] =
								'?' . $mapper['from'];

							$name = preg_replace(
								'/\[\d*\]/',
								'',
								$mapper['to']
							);
						}
					}
				}
			}

			if ( $is_file && $is_multi ) {
				$name = $field['name'];

				$len = count( $this->data['mutations'][0] ?? array() );
				for ( $i = 0; $i < $len; $i++ ) {
					$mapper = $this->data['mutations'][0][ $i ];

					$from = preg_replace( '/\[\d*\]/', '', $mapper['from'] );
					$from = preg_replace( '/^\?/', '', $mapper['from'] );

					if ( $from !== $name && $from !== $name . '_filename' ) {
						continue;
					}

					$this->data['mutations'][0][ $i ]['from'] =
						$mapper['from'] . '_1';
					$this->data['mutations'][0][ $i ]['to']   =
						$mapper['to'] . '_1';

					for ( $j = 2; $j < 10; $j++ ) {
						$from =
							strstr( $mapper['from'], '?' ) ?:
							'?' . $mapper['from'];

						$this->data['mutations'][0][] = array(
							'from' => $from . '_' . $j,
							'to'   => $mapper['to'] . '_' . $j,
							'cast' => $mapper['cast'],
						);
					}
				}
			}
		}
	}

	/**
	 * Returns an array with bridge tag values.
	 *
	 * @param string $tag Tag name.
	 *
	 * @return array
	 */
	private static function get_tag_value( $tag ) {
		switch ( $tag ) {
			case 'site_title':
				return get_bloginfo( 'name' );
			case 'site_description':
				return get_bloginfo( 'description' );
			case 'blog_url':
				return get_bloginfo( 'wpurl' );
			case 'site_url':
				return get_bloginfo( 'url' );
			case 'admin_email':
				return get_bloginfo( 'admin_email' );
			case 'wp_version':
				return get_bloginfo( 'version' );
			case 'ip_address':
				if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
					return sanitize_text_field(
						wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] )
					);
				} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
					return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
				}
				break;
			case 'referer':
				if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
					return sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
				}
				break;
			case 'user_agent':
				if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
					return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
				}
				break;
			case 'browser_locale':
				if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
					return sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) );
				}
				break;
			case 'locale':
				return get_locale();
			case 'language':
				include_once ABSPATH . 'wp-admin/includes/translation-install.php';
				$translations = wp_get_available_translations();
				$locale       = get_locale();
				return $translations[ $locale ]['native_name'] ?? $locale;
			case 'datetime':
				return date( 'Y-m-d H:i:s', time() );
			case 'gmt_datetime':
				return gmdate( 'Y-m-d H:i:s', time() );
			case 'timestamp':
				return time();
			case 'iso_date':
				return date( 'c', time() );
			case 'gmt_iso_date':
				return gmdate( 'c', time() );
			case 'utc_date':
				$date = gmdate( 'c', time() );
				return preg_replace( '/\+\d+\:\d+$/', 'Z', $date );
			case 'user_id':
				return wp_get_current_user()->ID;
			case 'user_login':
				return wp_get_current_user()->user_login;
			case 'user_name':
				return wp_get_current_user()->display_name;
			case 'user_email':
				return wp_get_current_user()->user_email;
			case 'submission_id':
				return FBAPI::get_submission_id();
			case 'form_title':
				$form = FBAPI::get_current_form();
				return $form['title'] ?? null;
			case 'form_id':
				$form = FBAPI::get_current_form();
				return $form['id'] ?? null;
		}
	}

	/**
	 * Adds bridge's custom fields to a payload.
	 *
	 * @param array $payload Bridge payload.
	 *
	 * @return array
	 */
	final public function add_custom_fields( $payload = array() ) {
		if ( ! is_array( $payload ) ) {
			return $payload;
		}

		$finger = new JSON_Finger( $payload );

		$custom_fields = $this->custom_fields ?: array();

		foreach ( $custom_fields as $custom_field ) {
			$is_value = JSON_Finger::validate( $custom_field['name'] );
			if ( ! $is_value ) {
				continue;
			}

			$value = $this->replace_field_tags( $custom_field['value'] );
			$finger->set( $custom_field['name'], $value );
		}

		return $finger->data();
	}

	/**
	 * Replace magic tags from the value.
	 *
	 * @param string $value Target value.
	 *
	 * @return string
	 */
	private function replace_field_tags( $value ) {
		foreach ( self::TAGS as $tag ) {
			if ( false !== strstr( $value, '$' . $tag ) ) {
				$value = str_replace( '$' . $tag, $this->get_tag_value( $tag ), $value );
			}
		}

		return $value;
	}

	/**
	 * Returns a clone of the bridge instance with its data patched by
	 * the partial array.
	 *
	 * @param array $partial Bridge data.
	 *
	 * @return Form_Bridge
	 */
	public function patch( $partial = array() ) {
		if ( ! $this->is_valid ) {
			return $this;
		}

		$data = array_merge( $this->data, $partial );
		return new static( $data, $this->addon );
	}

	/**
	 * Save the bridge data in the database.
	 *
	 * @return boolean
	 */
	public function save() {
		if ( ! $this->is_valid ) {
			return false;
		}

		$setting = Settings_Store::setting( $this->addon );
		if ( ! $setting ) {
			return false;
		}

		$bridges = $setting->bridges ?: array();

		$index = array_search( $this->name, array_column( $bridges, 'name' ), true );

		if ( false === $index ) {
			$bridges[] = $this->data;
		} else {
			$bridges[ $index ] = $this->data;
		}

		$setting->bridges = $bridges;

		return true;
	}

	/**
	 * Removes the bridge from the database.
	 *
	 * @return boolean
	 */
	public function delete() {
		if ( ! $this->is_valid ) {
			return false;
		}

		$setting = Settings_Store::setting( $this->addon );
		if ( ! $setting ) {
			return false;
		}

		$bridges = $setting->bridges ?: array();

		$index = array_search( $this->name, array_column( $bridges, 'name' ), true );

		if ( false === $index ) {
			return false;
		}

		array_splice( $bridges, $index, 1 );
		$setting->bridges = $bridges;

		return true;
	}
}
