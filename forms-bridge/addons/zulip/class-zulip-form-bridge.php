<?php
/**
 * Class Zulip_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FBAPI;
use HTTP_BRIDGE\Backend;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implamentation for the Zulip API.
 */
class Zulip_Form_Bridge extends Form_Bridge {

	/**
	 * Submits payload and attachments to the bridge's backend.
	 *
	 * @param array $payload Payload data.
	 * @param array $attachments Submission's attached files.
	 *
	 * @return array|WP_Error Http request response.
	 */
	public function submit( $payload = array(), $attachments = array() ) {
		$uploads = FBAPI::get_uploads();

		if ( ! empty( $uploads ) ) {
			$backend = $this->backend()->clone(
				array(
					'headers' => array(
						array(
							'name'  => 'Content-Type',
							'value' => 'multipart/form-data',
						),
					),
				)
			);

			$annex = "\n\n----\n" . esc_html( __( 'Attachments', 'forms-bridge' ) ) . ":\n";

			$attachments = Forms_Bridge::attachments( $uploads );

			foreach ( $attachments as $name => $path ) {
				$response = $backend->post( '/api/v1/user_uploads', array(), array(), array( $name => $path ) );

				if ( is_wp_error( $response ) ) {
					return $response;
				} elseif ( 'success' !== $response['data']['result'] ) {
					return new WP_Error( 'zulip_upload', __( 'Can not upload a file to Zulip', 'forms-bridge' ), $response['data'] );
				}

				unset( $payload[ $name ] );
				unset( $payload[ $name . '_filename' ] );

				$annex .= "* [{$name}]({$response['data']['url']})\n";
			}

			$payload['content'] .= $annex;
		}

		return parent::submit( $payload, array() );
	}
}
