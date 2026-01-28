<?php
/**
 * Class Airtable_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implementation for the Airtable service.
 */
class Airtable_Form_Bridge extends Form_Bridge {

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'airtable' );
	}

	/**
	 * Gets the base id from the bridge endpoint.
	 *
	 * @return string|null
	 */
	private function base_id() {
		preg_match( '/\/v\d+\/([^\/]+)\/([^\/]+)/', $this->endpoint, $matches );

		if ( 3 !== count( $matches ) ) {
			return null;
		}

		return $matches[1];
	}

	/**
	 * Gets the table id from the bridge endpoint.
	 *
	 * @return string|null
	 */
	private function table_id() {
		preg_match( '/\/v\d+\/([^\/]+)\/([^\/]+)/', $this->endpoint, $matches );

		if ( 3 !== count( $matches ) ) {
			return null;
		}

		return explode( '/', $matches[2] )[0];
	}

	/**
	 * Fetches the fields of the Airtable table and returns them as an array.
	 *
	 * @return array<mixed>|WP_Error
	 */
	public function get_fields() {
		if ( ! $this->is_valid ) {
			return new WP_Error( 'invalid_bridge', 'The bridge is invalid', $this->data );
		}

		$base_id  = $this->base_id();
		$table_id = $this->table_id();

		if ( ! $base_id || ! $table_id ) {
			return new WP_Error( 'invalid_endpoint', 'The bridge has an invalid  endpoint', $this->data );
		}

		$response = $this->patch(
			array(
				'method'   => 'GET',
				'endpoint' => "/v0/meta/bases/{$base_id}/tables",
			)
		)->submit();

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		foreach ( $response['data']['tables'] as $candidate ) {
			if ( $table_id === $candidate['id'] || $table_id === $candidate['name'] ) {
				$table = $candidate;
			}
		}

		if ( ! isset( $table ) ) {
			return new WP_Error( 'not_found', 'Table not found', $this->data );
		}

		return $table['fields'];
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

		$endpoint = $this->endpoint;
		$method   = $this->method;

		if ( 'POST' === $method ) {
			$fields = $this->get_fields( $backend );
			if ( is_wp_error( $fields ) ) {
				return $fields;
			}

			$payload = self::flatten_payload( $payload );

			$record = array();
			foreach ( $fields as $field ) {
				$field_name = $field['name'];
				if ( isset( $payload[ $field_name ] ) ) {
					$record['fields'][ $field_name ] = $payload[ $field_name ];
				}
			}

			$payload = array(
				'records' => array( $record ),
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
			} elseif ( wp_is_numeric_array( $value ) ) {
				$flat[ $key ] = array_map(
					function ( $value ) {
						return array( 'name' => $value );
					},
					$value,
				);
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
				return $simple_items;
			}
		}

		return self::flatten_payload( $value, $path . '.' );
	}
}
