<?php
/**
 * Class Rocketchat_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-rocketchat-form-bridge.php';
require_once 'hooks.php';

/**
 * RocketChat addon class
 */
class Rocketchat_Addon extends Addon {
	/**
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Rocket.Chat';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'rocketchat';

	/**
	 * Holds the addon's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Rocketchat_Form_Bridge';

	/**
	 * Holds the OpenAPI Specification URL.
	 *
	 * @var string
	 */
	public const OAS_URL = 'https://raw.githubusercontent.com/RocketChat/Rocket.Chat-Open-API/refs/heads/main/messaging.yaml';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Rocketchat_Form_Bridge(
			array(
				'name'     => '__rocketchat-' . time(),
				'endpoint' => '/api/v1/users.list',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit( array( 'status' => 'active' ) );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Rocket.Chat backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Fetch available models from the OAS spec.
	 *
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array
	 *
	 * @todo Implementar el endpoint de consulta de endpoints disponibles.
	 */
	public function get_endpoints( $backend, $method = null ) {
		if ( function_exists( 'yaml_parse' ) ) {
			$response = wp_remote_get( self::OAS_URL );

			if ( ! is_wp_error( $response ) ) {
				$data = yaml_parse( $response['body'] );
			}
		}

		if ( empty( $data ) ) {
			$contents = file_get_contents( FORMS_BRIDGE_ADDONS_DIR . '/rocketchat/assets/openapi.json' );
			$data     = json_decode( $contents, true );
		}

		if ( ! $data ) {
			return array( '/api/v1/chat.postMessage' );
		}

		$oa_explorer = new OpenAPI( $data );
		$paths       = $oa_explorer->paths();

		if ( $method ) {
			$method       = strtolower( $method );
			$method_paths = array();

			foreach ( $paths as $path ) {
				$path_obj = $oa_explorer->path_obj( $path );

				if ( $path_obj && isset( $path_obj[ $method ] ) ) {
					$method_paths[] = $path;
				}
			}

			$paths = $method_paths;
		}

		return $paths;
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields.
	 *
	 * @param string      $endpoint API endpoint.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		if ( function_exists( 'yaml_parse' ) ) {
			$response = wp_remote_get( self::OAS_URL );

			if ( ! is_wp_error( $response ) ) {
				$data = yaml_parse( $response['body'] );
			}
		}

		if ( empty( $data ) ) {
			$contents = file_get_contents( FORMS_BRIDGE_ADDONS_DIR . '/rocketchat/assets/openapi.json' );
			$data     = json_decode( $contents, true );
		}

		if ( ! $data ) {
			return array();
		}

		$oa_explorer = new OpenAPI( $data );

		$method = strtolower( $method ?? 'post' );
		$source = in_array( $method, array( 'post', 'put', 'patch' ), true ) ? 'body' : 'query';
		$params = $oa_explorer->params( $endpoint, $method, $source );

		return self::expand_endpoint_schema( $params ?: array() );
	}
}

Rocketchat_Addon::setup();
