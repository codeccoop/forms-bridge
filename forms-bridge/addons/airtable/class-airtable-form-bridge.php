<?php
/**
 * Class Airtable_Form_Bridge
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

		$backend = $this->backend;
		if ( ! $backend ) {
			return new WP_Error( 'invalid_backend', 'The bridge backend is unkown or invalid', $this->data );
		}

		$base_id  = $this->base_id();
		$table_id = $this->table_id();

		if ( ! $base_id || ! $table_id ) {
			return new WP_Error( 'invalid_endpoint', 'The bridge has an invalid endpoint', $this->data );
		}

		$endpoint = "/v0/meta/bases/{$base_id}/tables";
		$response = $backend->get( $endpoint );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		foreach ( $response['data']['tables'] as $candidate ) {
			if ( $table_id === $candidate['id'] || $table_id === $candidate['name'] ) {
				$table = $candidate;
				break;
			}
		}

		if ( ! isset( $table ) ) {
			return new WP_Error( 'not_found', 'Table not found', $this->data );
		}

		$fields = array();
		foreach ( $table['fields'] as $air_field ) {
			if (
				in_array(
					$air_field['type'],
					array(
						'aiText',
						'formula',
						'autoNumber',
						'button',
						'count',
						'createdBy',
						'createdTime',
						'lastModifiedBy',
						'lastModifiedTime',
						'rollup',
						'externalSyncSource',
						'multipleCollaborators',
						'multipleLookupValues',
						'multipleRecordLinks',
					),
					true,
				)
			) {
				continue;
			}

			$field = array(
				'id'    => $air_field['id'],
				'name'  => $air_field['name'],
				'label' => $air_field['name'],
			);

			switch ( $air_field['type'] ) {
				case 'multipleAttachments':
					$field['type']     = 'file';
					$field['is_multi'] = true;
					break;
				case 'rating':
				case 'number':
					$field['type'] = 'number';
					break;
				case 'checkbox':
					$field['type'] = 'checkbox';
					break;
				case 'multipleSelects':
				case 'singleSelect':
					$field['type']    = 'select';
					$field['options'] = array_map(
						function ( $choice ) {
							return array(
								'value' => $choice['name'],
								'label' => $choice['name'],
							);
						},
						$air_field['options']['choices'],
					);

					$field['is_multi'] = 'multipleSelects' === $air_field['type'];
					break;
				case 'date':
					$field['type'] = 'date';
					break;
				case 'multilineText':
					$field['type'] = 'textarea';
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
							'id'   => $attachment_field['id'],
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

			$record = array();
			foreach ( $data_fields as $data_field ) {
				$field_name = $data_field['name'];

				if ( isset( $payload[ $field_name ] ) ) {
					if (
						'select' === $data_field['type']
						&& $data_field['is_multi']
						&& ! is_array( $payload[ $field_name ] )
					) {
						$payload[ $field_name ] = array( $payload[ $field_name ] );
					}

					$record['fields'][ $field_name ] = $payload[ $field_name ];
				}
			}

			$payload = array(
				'records' => array( $record ),
			);
		}

		$response = $backend->$method( $endpoint, $payload );

		if ( is_wp_error( $response ) || empty( $response['data']['records'] ) ) {
			return $response;
		}

		if ( 'POST' === $method && count( $attachments ) ) {
			$base_id   = $this->base_id();
			$record_id = $response['data']['records'][0]['id'];

			$uploads = Forms_Bridge::attachments( FBAPI::get_uploads() );

			foreach ( $attachments as $attachment ) {
				$filetype = array( 'type' => 'octet/stream' );
				$filename = $attachment['name'];

				foreach ( $uploads as $upload_name => $path ) {
					if ( $upload_name === $attachment['key'] || $upload_name === sanitize_title( $attachment['key'] ) ) {
						$filename = basename( $path );
						$filetype = wp_check_filetype( $path );
						if ( empty( $filetype['type'] ) ) {
							$filetype['type'] = mime_content_type( $path ) ?: 'octet/stream';
						}
					}
				}

				$upload_response = $backend->clone(
					array(
						'name'     => '__airtable-uploader',
						'base_url' => 'https://content.airtable.com',
					)
				)->post(
					"/v0/{$base_id}/{$record_id}/{$attachment['id']}/uploadAttachment",
					array(
						'contentType' => $filetype['type'] ?? 'octet/stream',
						'file'        => $attachment['file'],
						'filename'    => $filename,
					),
				);

				if ( is_wp_error( $upload_response ) ) {
					return $upload_response;
				}
			}
		}

		return $response;
	}
}
