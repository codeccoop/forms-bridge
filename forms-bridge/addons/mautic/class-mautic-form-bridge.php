<?php
/**
 * Class Mautic_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implementation for the Mautic REST API.
 */
class Mautic_Form_Bridge extends Form_Bridge {

	/**
	 * Holds the keys of the Mautic API collection responses. Mautic serializes
	 * its collections as objects indexed by entity ID.
	 *
	 * @var string[]
	 */
	private const COLLECTIONS = array( 'contacts', 'companies', 'lists', 'fields' );

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'mautic' );
	}

	/**
	 * Submits payload to the Mautic API. On contact creation, the contact is
	 * added to the segments listed on the `segments` payload field.
	 *
	 * @param array $payload Payload data.
	 * @param array $attachments Submission's attached files.
	 *
	 * @return array|WP_Error Http request response.
	 */
	public function submit( $payload = array(), $attachments = array() ) {
		$segments = array();

		if ( 'POST' === $this->method && '/api/contacts/new' === $this->endpoint ) {
			$segments = self::segment_ids( $payload['segments'] ?? array() );
			unset( $payload['segments'] );
		}

		$response = parent::submit( $payload, $attachments );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! empty( $response['data']['errors'] ) ) {
			return new WP_Error(
				'mautic_api_error',
				'Mautic bridge response error',
				array( 'response' => $response )
			);
		}

		if ( 'GET' === $this->method && is_array( $response['data'] ) ) {
			foreach ( self::COLLECTIONS as $collection ) {
				if ( isset( $response['data'][ $collection ] ) && is_array( $response['data'][ $collection ] ) ) {
					$response['data'][ $collection ] = array_values( $response['data'][ $collection ] );
				}
			}
		}

		if ( empty( $segments ) ) {
			return $response;
		}

		$contact_id = $response['data']['contact']['id'] ?? null;
		if ( ! $contact_id ) {
			return new WP_Error(
				'mautic_segments_error',
				'Mautic contact ID not found on the API response',
				array( 'response' => $response )
			);
		}

		$backend = $this->backend();
		foreach ( $segments as $segment_id ) {
			$segment_response = $backend->post(
				"/api/segments/{$segment_id}/contact/{$contact_id}/add"
			);

			if ( is_wp_error( $segment_response ) ) {
				return $segment_response;
			}

			if ( empty( $segment_response['data']['success'] ) ) {
				return new WP_Error(
					'mautic_segments_error',
					'Mautic can not add the contact to the segment',
					array(
						'segment'  => $segment_id,
						'response' => $segment_response,
					)
				);
			}
		}

		return $response;
	}

	/**
	 * Normalizes the segments payload field value to a list of segment IDs.
	 *
	 * @param mixed $value Segments field value.
	 *
	 * @return int[]
	 */
	private static function segment_ids( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		} elseif ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		$ids = array();
		foreach ( $value as $id ) {
			$id = (int) $id;
			if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}
}
