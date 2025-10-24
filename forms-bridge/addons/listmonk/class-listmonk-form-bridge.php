<?php

namespace FORMS_BRIDGE;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implamentation for the REST API protocol.
 */
class Listmonk_Form_Bridge extends Form_Bridge {

	public function __construct( $data ) {
		parent::__construct( $data, 'listmonk' );
	}

	/**
	 * Performs an http request to backend's REST API.
	 *
	 * @param array $payload Payload data.
	 * @param array $attachments Submission's attached files.
	 *
	 * @return array|WP_Error
	 */
	public function submit( $payload = array(), $attachments = array() ) {
		$response = parent::submit( $payload, $attachments );

		if ( is_wp_error( $response ) ) {
			$error_response = $response->get_error_data()['response'] ?? null;

			$code = $error_response['response']['code'] ?? null;
			if ( $code !== 409 ) {
				return $response;
			}

			if (
				! isset( $payload['email'] ) ||
				$this->endpoint !== '/api/subscribers'
			) {
				return $response;
			}

			$get_response = $this->patch(
				array(
					'name'   => 'listmonk-get-subscriber-by-email',
					'method' => 'GET',
				)
			)->submit(
				array(
					'per_page' => '1',
					'query'    => "subscribers.email = '{$payload['email']}'",
				)
			);

			if ( is_wp_error( $get_response ) ) {
				return $response;
			}

			$subscriber_id = $get_response['data']['data']['results'][0]['id'];

			return $this->patch(
				array(
					'name'     => 'listmonk-update-subscriber',
					'method'   => 'PUT',
					'endpoint' => $this->endpoint . '/' . $subscriber_id,
				)
			)->submit( $payload );
		}

		return $response;
	}
}
