<?php
/**
 * Class Mailchimp_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use TypeError;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implamentation for the Mailchimp API.
 */
class Mailchimp_Form_Bridge extends Form_Bridge {

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'mailchimp' );
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
			if ( 400 !== $code ) {
				return $response;
			}

			try {
				$body  = json_decode( $error_response['body'] ?? '', true );
				$title = $body['title'] ?? null;
			} catch ( TypeError ) {
				return $response;
			}

			if ( 'Member Exists' === $title ) {
				if (
					! preg_match(
						'/(?<=lists\/).+(?=\/members)/',
						$this->endpoint,
						$matches
					)
				) {
					return $response;
				}

				$list_id = $matches[0];

				$search_response = $this->patch(
					array(
						'name'     => 'mailchimp-search-member',
						'method'   => 'GET',
						'endpoint' => '/3.0/search-members',
					)
				)->submit(
					array(
						'fiels'   => 'exact_matches.members.id',
						'list_id' => $list_id,
						'query'   => $payload['email_address'],
					)
				);

				if ( is_wp_error( $search_response ) ) {
					return $response;
				}

				$member_id =
					$search_response['data']['exact_matches']['members'][0]['id'] ?? null;

				if ( ! $member_id ) {
					return $response;
				}

				$update_endpoint = "/3.0/lists/{$list_id}/members/{$member_id}";
				if (
					strstr( $this->endpoint, 'skip_merge_validation' ) !== false
				) {
					$update_endpoint .= '?skip_merge_validation=true';
				}

				$update_response = $this->patch(
					array(
						'name'     => 'mailchimp-update-subscription',
						'method'   => 'PUT',
						'endpoint' => $update_endpoint,
					)
				)->submit( $payload );

				if ( is_wp_error( $update_response ) ) {
					return $response;
				}

				return $update_response;
			}
		}

		return $response;
	}
}
