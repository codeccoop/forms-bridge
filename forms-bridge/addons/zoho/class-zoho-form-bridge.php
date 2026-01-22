<?php
/**
 * Class Zoho_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implamentation for the Zoho API protocol.
 */
class Zoho_Form_Bridge extends Form_Bridge {

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array  $data Bridge data.
	 * @param string $addon Addon name, default to zoho.
	 */
	public function __construct( $data, $addon = 'zoho' ) {
		parent::__construct( $data, $addon );
	}

	/**
	 * Performs an http request to the Zoho API backend.
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

		$method = $this->method;
		if ( 'POST' === $method || 'PUT' === $method ) {
			$payload = wp_is_numeric_array( $payload ) ? $payload : array( $payload );
			$payload = array( 'data' => $payload );
		}

		add_filter(
			'http_bridge_backend_headers',
			function ( $headers, $backend ) {
				if ( $backend->name === $this->data['backend'] ) {
					if ( isset( $headers['Authorization'] ) ) {
						$headers['Authorization'] = str_replace(
							'Bearer',
							'Zoho-oauthtoken',
							$headers['Authorization']
						);
					}
				}

				return $headers;
			},
			9,
			2
		);

		$backend = $this->backend();

		if ( ! $backend ) {
			return new WP_Error(
				'invalid_backend',
				'The bridge does not have a valid backend'
			);
		}

		$response = $backend->$method(
			$this->endpoint,
			$payload,
			array(),
			$attachments
		);

		if ( is_wp_error( $response ) ) {
			$data = json_decode(
				$response->get_error_data()['response']['body'] ?? '{}',
				true
			);

			$code = $data['data'][0]['code'] ?? null;
			if ( 'DUPLICATE_DATA' !== $code ) {
				return $response;
			}

			$response         = $response->get_error_data()['response'];
			$response['data'] = json_decode( $response['body'], true );
		}

		return $response;
	}
}
