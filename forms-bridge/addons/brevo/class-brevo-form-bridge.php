<?php
/**
 * Class Brevo_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implamentation for the REST API protocol.
 */
class Brevo_Form_Bridge extends Form_Bridge {

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'brevo' );
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
			if (
				! $error_response ||
				( 425 !== $error_response['response']['code'] &&
				400 !== $error_response['response']['code'] )
			) {
				return $response;
			}

			$data = json_decode( $error_response['body'], true );
			if ( 'duplicate_parameter' !== $data['code'] ) {
				return $response;
			}

			if (
				! isset( $payload['email'] ) ||
				strstr( $this->endpoint, '/v3/contacts' ) === false
			) {
				return $response;
			}

			$update_response = $this->patch(
				array(
					'name'     => 'brevo-update-contact-by-email',
					'endpoint' => "/v3/contacts/{$payload['email']}?identifierType=email_id",
					'method'   => 'PUT',
				)
			)->submit( $payload );

			if ( is_wp_error( $update_response ) ) {
				return $update_response;
			}

			return $this->patch(
				array(
					'name'     => 'brevo-search-contact-by-email',
					'endpoint' => "/v3/contacts/{$payload['email']}",
					'method'   => 'GET',
				)
			)->submit( array( 'identifierType' => 'email_id' ) );
		}

		return $response;
	}
}
