<?php
/**
 * Class Slack_Form_Bridge
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
 * Form bridge implementation for the Slack API.
 */
class Slack_Form_Bridge extends Form_Bridge {
	/**
	 * Holdes the current HTTP request.
	 *
	 * @var array|null
	 */
	private static $request;

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
			$attachments = Forms_Bridge::attachments( $uploads );
			$backend     = $this->backend();

			foreach ( $attachments as $name => $path ) {
				$info     = pathinfo( $path );
				$filename = $info['basename'];

				$response = $backend->post(
					'/api/files.getUploadURLExternal',
					array(
						'length'   => filesize( $path ),
						'filename' => $filename,
					),
					array(
						'Content-Type' => 'application/x-www-form-urlencoded',
					)
				);

				if ( is_wp_error( $response ) ) {
					return $response;
				}

				if ( isset( $response['data']['error'] ) ) {
					return new WP_Error( 'slack_upload', __( 'Can not upload a file to Slack', 'forms-bridge' ), $response['data'] );
				}

				$file_id    = $response['data']['file_id'];
				$upload_url = $response['data']['upload_url'];

				$response = http_bridge_post(
					$upload_url,
					file_get_contents( $path ),
					array(
						'Content-Type' => 'application/octet-stream',
					),
				);

				if ( is_wp_error( $response ) ) {
					return $response;
				}

				$attachments[ $name ] = $file_id;
			}

			$files = array();
			foreach ( $attachments as $name => $file_id ) {
				$files[] = array(
					'id'    => $file_id,
					'title' => $filename,
				);
			}

			$response = $backend->post(
				'/api/files.completeUploadExternal',
				array( 'files' => wp_json_encode( $files ) ),
				array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( isset( $response['data']['error'] ) ) {
				return new WP_Error( 'slack_upload', __( 'Can not upload a file to Slack', 'forms-bridge' ), $response['data'] );
			}

			if ( isset( $payload['markdown_text'] ) ) {
				$annex = "\n\n**" . esc_html( __( 'Attachments', 'forms-bridge' ) ) . "**:\n";

				foreach ( $response['data']['files'] as $upload ) {
					$annex .= "* [{$upload['name']}]({$upload['permalink']})\n";
				}

				$payload['markdown_text'] .= $annex;
			} else {
				$annex = "\n\n" . esc_html( __( 'Attachments', 'forms-bridge' ) ) . ":\n";

				foreach ( $response['data']['files'] as $upload ) {
					$annex .= "* {$upload['name']}: {$upload['permalink']}\n";
				}

				$payload['text'] .= $annex;
			}
		}

		add_filter(
			'http_bridge_request',
			static function ( $request ) {
				self::$request = $request;
				return $request;
			},
			10,
			1
		);

		$response = parent::submit( $payload, array() );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response['data']['error'] ) ) {
			return new WP_Error(
				'slack_rpc_error',
				'Slack bridge response error',
				array(
					'request'  => self::$request,
					'response' => $response,
				)
			);
		}

		return $response;
	}
}
