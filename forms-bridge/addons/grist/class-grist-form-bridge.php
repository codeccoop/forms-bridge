<?php
/**
 * Class Grist_Form_Bridge
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
	 * Gets the document id from the bridge endpoint.
	 *
	 * @return string|null
	 */
	private function doc_id() {
		preg_match( '/(?<=docs\/)[^\/]+/', $this->endpoint, $matches );

		if ( empty( $matches[0] ) ) {
			return null;
		}

		return $matches[0];
	}

	/**
	 * Gets the table id from the bridge endpoint.
	 *
	 * @return string|null
	 */
	private function table_id() {
		preg_match( '/(?<=tables\/)[^\/]+/', $this->endpoint, $matches );

		if ( empty( $matches[0] ) ) {
			return null;
		}

		return $matches[0];
	}

	/**
	 * Fetches the fields of the Grist table and returns them as an array.
	 *
	 * @return array<mixed>|WP_Error
	 */
	public function get_fields() {
		if ( ! $this->is_valid ) {
			return new WP_Error( 'invalid_bridge', 'The bridge is invalid', $this->data );
		}

		$backend = $this->backend;
		if ( ! $backend ) {
			return new WP_Error( 'invalid_backend', 'The bridge backend is unkown or invalid', $this->data );
		}

		$doc_id   = $this->doc_id();
		$table_id = $this->table_id();

		if ( ! $doc_id || ! $table_id ) {
			return new WP_Error( 'invalid_endpoint', 'The bridge has an invalid endpoint', $this->data );
		}

		$endpoint = "/api/docs/{$doc_id}/tables/{$table_id}/columns";
		$response = $backend->get( $endpoint );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$fields = array();
		foreach ( $response['data']['columns'] as $column ) {
			if (
				$column['fields']['isFormula']
				|| $column['fields']['formula']
				|| 0 === strpos( $column['fields']['type'], 'Ref:' )
				|| 0 === strpos( $column['fields']['type'], 'RefList:' )
			) {
				continue;
			}

			$field = array(
				'name'  => $column['id'],
				'label' => $column['fields']['label'],
			);

			switch ( $column['fields']['type'] ) {
				case 'Attachments':
					$field['type'] = 'file';
					break;
				case 'Choice':
				case 'ChoiceList':
					$field['type']     = 'select';
					$field['is_multi'] = 'ChoiceList' === $column['fields']['type'];

					$options          = json_decode( $column['fields']['widgetOptions'], true ) ?: array( 'choices' => array() );
					$field['options'] = array_map(
						function ( $choice ) {
							return array(
								'value' => $choice,
								'label' => $choice,
							);
						},
						$options['choices'],
					);

					break;
				case 'Bool':
					$field['type'] = 'checkbox';
					break;
				case 'Int':
				case 'Numeric':
					$field['type'] = 'number';
					break;
				case 'Date':
					$field['type'] = 'date';
					break;
				default:
					$field['type'] = 'text';
					break;
			}

			$fields[] = $field;
		}

		return $fields;
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

			$data_fields = array();
			$attachments = array();

			$l = count( $fields );
			for ( $i = 0; $i < $l; ++$i ) {
				if ( 'file' === $fields[ $i ]['type'] ) {
					$attachment_field = $fields[ $i ];
					$attachment_name  = $attachment_field['name'];

					$names = array_keys( $payload );
					$keys  = array_filter(
						$names,
						function ( $name ) use ( $attachment_name ) {
							$name = preg_replace( '/_\d+$/', '', $name );
							return $name === $attachment_name;
						}
					);

					foreach ( $keys as $key ) {
						$attachments[] = array(
							'file' => $payload[ $attachment_name ],
							'name' => $attachment_name,
							'key'  => $key,
						);

						unset( $payload[ $key ] );
						unset( $payload[ $key . '_filename' ] );
					}
				} else {
					$data_fields[] = $fields[ $i ];
				}
			}

			$record = array( 'fields' => array() );

			if ( count( $attachments ) ) {
				$doc_id = $this->doc_id();

				$uploads = Forms_Bridge::attachments( FBAPI::get_uploads() );

				foreach ( $attachments as $attachment ) {
					foreach ( $uploads as $upload_name => $path ) {
						if ( $upload_name === $attachment['key'] || sanitize_title( $attachment['key'] ) === $upload_name ) {
							$attachment_path = $path;
							break;
						}
					}

					if ( ! isset( $attachment_path ) || ! is_file( $attachment_path ) ) {
						continue;
					}

					$upload_response = $backend->post(
						"/api/docs/{$doc_id}/attachments",
						array(),
						array( 'Content-Type' => 'multipart/form-data' ),
						array( 'upload' => $attachment_path ),
					);

					if ( is_wp_error( $upload_response ) ) {
						return $upload_response;
					}

					$record['fields'][ $attachment['name'] ] = $upload_response['data'][0];
				}
			}

			foreach ( $data_fields as $field ) {
				$field_name = $field['name'];
				if ( isset( $payload[ $field_name ] ) ) {
					if ( 'select' === $field['type'] && ( $field['is_multi'] ?? false ) ) {
						if ( ! is_array( $payload[ $field_name ] ) ) {
							$payload[ $field_name ] = array( $payload[ $field_name ] );
						}

						array_unshift( $payload[ $field_name ], 'L' );
					}

					$record['fields'][ $field_name ] = $payload[ $field_name ];
				}
			}

			$payload = array(
				'records' => array( $record ),
			);
		}

		return $this->backend->$method( $endpoint, $payload );
	}
}
