<?php
/**
 * Class Brevo_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-brevo-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * REST API Addon class.
 */
class Brevo_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Brevo';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'brevo';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Brevo_Form_Bridge';

	/**
	 * Holds the OAS URL.
	 *
	 * @var string
	 */
	public const OAS_URL = 'https://api.brevo.com/v3/swagger_definition_v3.yml';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => '__brevo-' . time(),
				'endpoint' => '/v3/contacts/lists',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit( array( 'limit' => 1 ) );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Brevo backend ping error response', Logger::ERROR );
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
			$contents = file_get_contents( FORMS_BRIDGE_ADDONS_DIR . '/brevo/assets/openapi.json' );
			$data     = json_decode( $contents, true );
		}

		if ( ! $data ) {
			return array( '/v3/contacts' );
		}

		$oa_explorer = new OpenAPI( $data );

		$paths = $oa_explorer->paths();

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

		$endpoints = array();
		foreach ( $paths as $path ) {
			$endpoints[] = '/v3' . $path;
		}

		return $endpoints;
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields
	 * and accepted content type.
	 *
	 * @param string      $endpoint API endpoint.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		if ( function_exists( 'yaml_parse' ) ) {
			$response = wp_remote_get( self::OAS_URL );

			if ( ! is_wp_error( $response ) ) {
				$data = yaml_parse( $response['body'] );
			}
		}

		if ( empty( $data ) ) {
			$contents = file_get_contents( FORMS_BRIDGE_ADDONS_DIR . '/brevo/assets/openapi.json' );
			$data     = json_decode( $contents, true );
		}

		if ( ! $data ) {
			return array();
		}

		$oa_explorer = new OpenAPI( $data );

		$method = strtolower( $method ?? 'post' );
		$path   = preg_replace( '/^\/v\d+/', '', $endpoint );
		$source = in_array( $method, array( 'post', 'put', 'patch' ), true ) ? 'body' : 'query';
		$params = $oa_explorer->params( $path, $method, $source );

		return OpenAPI::expand_fields_schema( $params ?: array() );
	}
}

Brevo_Addon::setup();
