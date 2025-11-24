<?php
/**
 * Class SuiteCRM_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use HTTP_BRIDGE\Http_Client;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implementation for the SuiteCRM v4_1 REST API.
 */
class SuiteCRM_Form_Bridge extends Form_Bridge {

	/**
	 * Handles the SuiteCRM v4_1 REST API endpoint.
	 *
	 * @var string
	 */
	private const ENDPOINT = '/service/v4_1/rest.php';

	/**
	 * Handles active session data.
	 *
	 * @var string|null Session ID from login.
	 */
	private static $session_id;

	/**
	 * Handles the addon's current request data for debugging.
	 *
	 * @var array|null
	 */
	private static $request;

	/**
	 * Build REST API payload for SuiteCRM v4_1.
	 *
	 * @param string $method API method name.
	 * @param array  $args Method arguments.
	 *
	 * @return array URL-encoded form data.
	 */
	public static function rest_payload( $method, $args = array() ) {
		return array(
			'method'        => $method,
			'input_type'    => 'JSON',
			'response_type' => 'JSON',
			'rest_data'     => wp_json_encode( $args ),
		);
	}

	/**
	 * Handle REST API responses and catch errors.
	 *
	 * @param array $res Request response.
	 *
	 * @return mixed|WP_Error Request result.
	 */
	public static function rest_response( $res ) {
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		if ( empty( $res['data'] ) ) {
			$content_type = Http_Client::get_content_type( $res['headers'] ) ?? 'undefined';

			return new WP_Error(
				'unknown_content_type',
				sprintf(
					/* translators: %s: Content-Type header value */
					esc_html( __( 'Unknown HTTP response content type %s', 'forms-bridge' ) ),
					sanitize_text_field( $content_type )
				),
				$res
			);
		}

		$data = $res['data'];

		// Check for SuiteCRM error response.
		if ( isset( $data['name'] ) && isset( $data['number'] ) && isset( $data['description'] ) ) {
			// This is an error response.
			if ( 'No Error' !== $data['name'] ) {
				$error = new WP_Error(
					'suitecrm_error_' . $data['number'],
					$data['description'],
					$data
				);

				$error_data = array( 'response' => $res );
				if ( self::$request ) {
					$error_data['request'] = self::$request;
				}

				$error->add_data( $error_data );
				return $error;
			}
		}

		return $data;
	}

	/**
	 * Login to SuiteCRM and get session ID.
	 *
	 * @param Credential $credential Bridge credential object.
	 * @param Backend    $backend Bridge backend object.
	 *
	 * @return string|WP_Error Session ID on success.
	 */
	private static function rest_login( $credential, $backend ) {
		if ( self::$session_id ) {
			return self::$session_id;
		}

		$username = $credential->client_id;
		$password = $credential->client_secret;

		// SuiteCRM v4_1 requires MD5 hashed password.
		$password_hash = md5( $password );

		$payload = self::rest_payload(
			'login',
			array(
				'user_auth'       => array(
					'user_name' => $username,
					'password'  => $password_hash,
				),
				'application'     => 'FormsBridge',
				'name_value_list' => array(),
			)
		);

		$response = $backend->post( self::ENDPOINT, $payload );

		$result = self::rest_response( $response );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( empty( $result['id'] ) ) {
			return new WP_Error(
				'login_failed',
				__( 'SuiteCRM login failed: No session ID returned', 'forms-bridge' ),
				$result
			);
		}

		self::$session_id = $result['id'];
		return self::$session_id;
	}

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'suitecrm' );
	}

	/**
	 * Submits submission to the backend.
	 *
	 * @param array $payload Submission data.
	 * @param array $more_args Additional arguments (e.g., select_fields for get_entry_list).
	 *
	 * @return array|WP_Error HTTP response.
	 */
	public function submit( $payload = array(), $more_args = array() ) {
		if ( ! $this->is_valid ) {
			return new WP_Error(
				'invalid_bridge',
				'Bridge data is invalid',
				(array) $this->data
			);
		}

		$backend = $this->backend();

		if ( ! $backend ) {
			return new WP_Error(
				'invalid_backend',
				'The bridge does not have a valid backend'
			);
		}

		$credential = $backend->credential;
		if ( ! $credential ) {
			return new WP_Error(
				'invalid_credential',
				'The bridge does not have a valid credential'
			);
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

		$session_id = self::rest_login( $credential, $backend );

		if ( is_wp_error( $session_id ) ) {
			return $session_id;
		}

		// Build the API request based on method.
		$rest_args = $this->build_rest_args( $session_id, $payload, $more_args );

		$api_payload = self::rest_payload( $this->method, $rest_args );

		$response = $backend->post( self::ENDPOINT, $api_payload );

		$result = self::rest_response( $response );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Normalize response.
		$response['data'] = $result;

		return $response;
	}

	/**
	 * Build REST API arguments based on the method.
	 *
	 * @param string $session_id Session ID.
	 * @param array  $payload Form submission payload.
	 * @param array  $more_args Additional arguments.
	 *
	 * @return array REST API arguments.
	 */
	private function build_rest_args( $session_id, $payload, $more_args ) {
		$module = $this->endpoint;

		switch ( $this->method ) {
			case 'get_available_modules':
				return array(
					'session' => $session_id,
				);

			case 'get_module_fields':
				return array(
					'session'     => $session_id,
					'module_name' => $module,
				);

			case 'get_entry_list':
				$args = array(
					'session'       => $session_id,
					'module_name'   => $module,
					'query'         => $more_args['query'] ?? '',
					'order_by'      => $more_args['order_by'] ?? '',
					'offset'        => $more_args['offset'] ?? 0,
					'select_fields' => $more_args['select_fields'] ?? array(),
					'max_results'   => $more_args['max_results'] ?? 20,
					'deleted'       => $more_args['deleted'] ?? 0,
				);
				return $args;

			case 'get_entry':
				return array(
					'session'                   => $session_id,
					'module_name'               => $module,
					'id'                        => $payload['id'] ?? '',
					'select_fields'             => $more_args['select_fields'] ?? array(),
					'link_name_to_fields_array' => array(),
					'track_view'                => false,
				);

			case 'set_entry':
				// Convert payload to name_value_list format.
				$name_value_list = array();
				foreach ( $payload as $name => $value ) {
					$name_value_list[] = array(
						'name'  => $name,
						'value' => $value,
					);
				}

				return array(
					'session'         => $session_id,
					'module_name'     => $module,
					'name_value_list' => $name_value_list,
				);

			case 'set_relationship':
				return array(
					'session'         => $session_id,
					'module_name'     => $module,
					'module_id'       => $payload['module_id'] ?? '',
					'link_field_name' => $payload['link_field_name'] ?? '',
					'related_ids'     => $payload['related_ids'] ?? array(),
					'name_value_list' => $payload['name_value_list'] ?? array(),
					'delete'          => $payload['delete'] ?? 0,
				);

			case 'get_relationships':
				return array(
					'session'              => $session_id,
					'module_name'          => $module,
					'module_id'            => $payload['module_id'] ?? '',
					'link_field_name'      => $payload['link_field_name'] ?? '',
					'related_module_query' => $more_args['query'] ?? '',
					'related_fields'       => $more_args['select_fields'] ?? array(),
				);

			default:
				// For custom methods, pass payload as-is with session.
				return array_merge(
					array( 'session' => $session_id ),
					$payload
				);
		}
	}
}
