<?php
/**
 * Class Grist_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implementation for the Grist service.
 */
class Grist_Form_Bridge extends Form_Bridge {

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'grist' );
	}

	/**
	 * Fetches the fields of the Grist table and returns them as an array.
	 *
	 * @param Backend|null $backend Bridge backend instance.
	 *
	 * @return array<mixed>|WP_Error
	 */
	public function get_fields( $backend = null ) {
		if ( ! $this->is_valid ) {
			return new WP_Error( 'invalid_bridge' );
		}

		if ( ! $backend ) {
			$backend = $this->backend;
		}

		// For Grist, we need to fetch the table schema
		// The endpoint should be something like /api/tables/{tableId}/schema
		$response = $backend->get( $this->endpoint );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Parse Grist schema response
		// Grist typically returns columns in a specific format
		if ( empty( $response['data']['columns'] ) ) {
			return array();
		}

		$fields = array();
		foreach ( $response['data']['columns'] as $column ) {
			$fields[] = array(
				'name' => $column['name'],
				'type' => $this->map_grist_type( $column['type'] ),
			);
		}

		return $fields;
	}

	/**
	 * Maps Grist column types to standard types.
	 *
	 * @param string $grist_type Grist column type.
	 *
	 * @return string
	 */
	private function map_grist_type( $grist_type ) {
		$type_mapping = array(
			'Text'     => 'string',
			'Numeric'  => 'number',
			'Bool'     => 'boolean',
			'Date'     => 'string',
			'DateTime' => 'string',
			'Choice'   => 'string',
			'Ref'      => 'string',
			'RefList'  => 'array',
		);

		return $type_mapping[ $grist_type ] ?? 'string';
	}

	/**
	 * Sends the payload to the backend.
	 *
	 * @param array $payload Submission data.
	 * @param array $attachments Submission's attached files. Will be ignored.
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

		$backend = $this->backend;
		if ( ! $backend ) {
			return new WP_Error( 'invalid_backend', 'Backend not found' );
		}

		$fields = $this->get_fields( $backend );
		if ( is_wp_error( $fields ) ) {
			return $fields;
		}

		$payload = self::flatten_payload( $payload );

		$records = array();
		foreach ( $fields as $field ) {
			$field_name = $field['name'];
			if ( isset( $payload[ $field_name ] ) ) {
				$records[ $field_name ] = $payload[ $field_name ];
			}
		}

		$endpoint = $this->endpoint;
		$method   = $this->method;

		if ( 'POST' === $method ) {
			$payload = array(
				'records' => array( $records ),
			);
		}

		return $this->backend->$method( $endpoint, $payload );
	}

	/**
	 * Flattens nested arrays in the payload and concatenates their keys as field names.
	 *
	 * @param array  $payload Submission payload.
	 * @param string $path Prefix to prepend to the field name.
	 *
	 * @return array Flattened payload.
	 */
	private static function flatten_payload( $payload, $path = '' ) {
		$flat = array();
		foreach ( $payload as $field => $value ) {
			$key   = $path . $field;
			$value = self::flatten_value( $value, $key );

			if ( ! is_array( $value ) ) {
				$flat[ $key ] = $value;
			} else {
				foreach ( $value as $_key => $_val ) {
					$flat[ $_key ] = $_val;
				}
			}
		}

		return $flat;
	}

	/**
	 * Returns array values as a flat vector of field key values.
	 *
	 * @param mixed  $value Payload value.
	 * @param string $path Hierarchical path to the value.
	 *
	 * @return mixed
	 */
	private static function flatten_value( $value, $path = '' ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		if ( wp_is_numeric_array( $value ) ) {
			$simple_items = array_filter( $value, fn( $item ) => ! is_array( $item ) );

			if ( count( $simple_items ) === count( $value ) ) {
				return implode( ',', $value );
			}
		}

		return self::flatten_payload( $value, $path . '.' );
	}
}
