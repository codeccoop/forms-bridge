<?php
/**
 * Class Vtiger_Form_Bridge
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
 * Form bridge implementation for the Vtiger Webservice REST API.
 */
class Vtiger_Form_Bridge extends Form_Bridge {

	/**
	 * Handles the Vtiger webservice API endpoint.
	 *
	 * @var string
	 */
	private const ENDPOINT = '/webservice.php';

	/**
	 * Handles active session data.
	 *
	 * @var string|null Session name from login.
	 */
	private static $session_name;

	/**
	 * Handles the user ID from login.
	 *
	 * @var string|null
	 */
	private static $user_id;

	/**
	 * Handles the addon's current request data for debugging.
	 *
	 * @var array|null
	 */
	private static $request;

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
			return new WP_Error(
				'bad_request',
				'Vtiger null response body',
				$res
			);
		}

		$data = $res['data'];

		// Check for Vtiger error response.
		if ( isset( $data['success'] ) && false === $data['success'] ) {
			$error_code    = $data['error']['code'] ?? 'unknown_error';
			$error_message = $data['error']['message'] ?? __( 'Unknown Vtiger error', 'forms-bridge' );

			$error = new WP_Error(
				'vtiger_' . $error_code,
				$error_message,
				$data
			);

			$error_data = array( 'response' => $res );
			if ( self::$request ) {
				$error_data['request'] = self::$request;
			}

			$error->add_data( $error_data );
			return $error;
		}

		return $data;
	}

	/**
	 * Get challenge token for authentication.
	 *
	 * @param string  $username Username for challenge.
	 * @param Backend $backend Bridge backend object.
	 *
	 * @return string|WP_Error Challenge token on success.
	 */
	private static function get_challenge( $username, $backend ) {
		$url = self::ENDPOINT . '?' . http_build_query(
			array(
				'operation' => 'getchallenge',
				'username'  => $username,
			)
		);

		$response = $backend->get( $url );
		$result   = self::rest_response( $response );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( empty( $result['result']['token'] ) ) {
			return new WP_Error(
				'challenge_failed',
				__( 'Vtiger challenge failed: No token returned', 'forms-bridge' ),
				$result
			);
		}

		return $result['result']['token'];
	}

	/**
	 * Login to Vtiger and get session name.
	 *
	 * @param Credential $credential Bridge credential object.
	 * @param Backend    $backend Bridge backend object.
	 *
	 * @return string|WP_Error Session name on success.
	 */
	private static function rest_login( $credential, $backend ) {
		if ( self::$session_name ) {
			return self::$session_name;
		}

		$username   = $credential->client_id ?? '';
		$access_key = $credential->client_secret ?? '';

		// Step 1: Get challenge token.
		$token = self::get_challenge( $username, $backend );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		// Step 2: Login with MD5(token + accessKey).
		$access_key_hash = md5( $token . $access_key );

		$payload = array(
			'operation' => 'login',
			'username'  => $username,
			'accessKey' => $access_key_hash,
		);

		$response = $backend->post( self::ENDPOINT, $payload );
		$result   = self::rest_response( $response );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( empty( $result['result']['sessionName'] ) ) {
			return new WP_Error(
				'login_failed',
				__( 'Vtiger login failed: No session returned', 'forms-bridge' ),
				$result
			);
		}

		self::$session_name = $result['result']['sessionName'];
		self::$user_id      = $result['result']['userId'] ?? '';

		return self::$session_name;
	}

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'vtiger' );
	}

	/**
	 * Submits submission to the backend.
	 *
	 * @param array $payload Submission data.
	 * @param array $more_args Additional arguments.
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
				unset( $request['args']['headers']['authentication'] );
				self::$request = $request;
				return $request;
			},
			10,
			1
		);

		// Login to get session.
		$session_name = self::rest_login( $credential, $backend );

		if ( is_wp_error( $session_name ) ) {
			return $session_name;
		}

		// Build and execute the API request.
		return $this->execute_operation( $session_name, $payload, $more_args, $backend );
	}

	/**
	 * Execute a Vtiger webservice operation.
	 *
	 * @param string  $session_name Session name.
	 * @param array   $payload Form submission payload.
	 * @param array   $more_args Additional arguments.
	 * @param Backend $backend Backend object.
	 *
	 * @return array|WP_Error HTTP response.
	 */
	private function execute_operation( $session_name, $payload, $more_args, $backend ) {
		$module = $this->endpoint;

		switch ( $this->method ) {
			case 'listtypes':
				$url      = self::ENDPOINT . '?' . http_build_query(
					array(
						'operation'   => 'listtypes',
						'sessionName' => $session_name,
					)
				);
				$response = $backend->get( $url );
				break;

			case 'describe':
				$url      = self::ENDPOINT . '?' . http_build_query(
					array(
						'operation'   => 'describe',
						'sessionName' => $session_name,
						'elementType' => $module,
					)
				);
				$response = $backend->get( $url );
				break;

			case 'query':
				$query    = $more_args['query'] ?? $payload['query'] ?? "SELECT * FROM {$module} LIMIT 20;";
				$url      = self::ENDPOINT . '?' . http_build_query(
					array(
						'operation'   => 'query',
						'sessionName' => $session_name,
						'query'       => $query,
					)
				);
				$response = $backend->get( $url );
				break;

			case 'retrieve':
				$url      = self::ENDPOINT . '?' . http_build_query(
					array(
						'operation'   => 'retrieve',
						'sessionName' => $session_name,
						'id'          => $payload['id'] ?? '',
					)
				);
				$response = $backend->get( $url );
				break;

			case 'create':
				// Add module type to element data.
				$element = array_merge(
					$payload,
					array( 'assigned_user_id' => $payload['assigned_user_id'] ?? self::$user_id )
				);

				$post_data = array(
					'operation'   => 'create',
					'sessionName' => $session_name,
					'elementType' => $module,
					'element'     => wp_json_encode( $element ),
				);

				$response = $backend->post( self::ENDPOINT, $post_data );
				break;

			case 'update':
				$post_data = array(
					'operation'   => 'update',
					'sessionName' => $session_name,
					'element'     => wp_json_encode( $payload ),
				);

				$response = $backend->post( self::ENDPOINT, $post_data );
				break;

			case 'delete':
				$post_data = array(
					'operation'   => 'delete',
					'sessionName' => $session_name,
					'id'          => $payload['id'] ?? '',
				);

				$response = $backend->post( self::ENDPOINT, $post_data );
				break;

			case 'sync':
				$url      = self::ENDPOINT . '?' . http_build_query(
					array(
						'operation'    => 'sync',
						'sessionName'  => $session_name,
						'elementType'  => $module,
						'modifiedTime' => $more_args['modifiedTime'] ?? 0,
					)
				);
				$response = $backend->get( $url );
				break;

			default:
				// For custom operations, try as GET first.
				$params = array_merge(
					array(
						'operation'   => $this->method,
						'sessionName' => $session_name,
					),
					$payload
				);

				if ( ! empty( $module ) ) {
					$params['elementType'] = $module;
				}

				$url      = self::ENDPOINT . '?' . http_build_query( $params );
				$response = $backend->get( $url );
				break;
		}

		$result = self::rest_response( $response );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Normalize response.
		$response['data'] = $result;

		return $response;
	}
}
