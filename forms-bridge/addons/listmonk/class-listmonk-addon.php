<?php
/**
 * Class Listmonk_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-listmonk-form-bridge.php';
require_once 'hooks.php';

/**
 * Listmonk Addon class.
 */
class Listmonk_Addon extends Addon {

	/**
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Listmonk';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'listmonk';

	/**
	 * Holds the addom's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Listmonk_Form_Bridge';

	/**
	 * Holds the addon's OAS URL.
	 *
	 * @var string
	 */
	public const OAS_URL = 'https://listmonk.app/docs/swagger/collections.yaml';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => '__listmonk-' . time(),
				'endpoint' => '/api/lists',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Listmonk backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend endpoint and retrive the response data.
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $backend Backend name.
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $endpoint, $backend ) {
		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => '__listmonk-' . time(),
				'method'   => 'GET',
				'endpoint' => $endpoint,
				'backend'  => $backend,
			)
		);

		return $bridge->submit();
	}

	/**
	 * Fetch available models from the OAS spec.
	 *
	 * @param Backend $backend HTTP backend object.
	 *
	 * @return array
	 *
	 * @todo Implementar el endpoint de consulta de endpoints disponibles.
	 */
	public function get_endpoints( $backend ) {
		if ( function_exists( 'yaml_parse' ) ) {
			$response = wp_remote_get( self::OAS_URL );

			if ( ! is_wp_error( $response ) ) {
				$data = yaml_parse( $response['body'] );

				$oa_explorer = new OpenAPI( $data );
				$paths       = $oa_explorer->paths();

				return array_map(
					function ( $path ) {
						return '/api' . $path;
					},
					$paths
				);
			}
		}

		return array(
			'/api/subscribers',
		);
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

				$oa_explorer = new OpenAPI( $data );

				$method = strtolower( $method ?? 'post' );
				$path   = preg_replace( '/^\/api/', '', $endpoint );
				$source = in_array( $method, array( 'post', 'put', 'patch' ), true ) ? 'body' : 'query';
				$params = $oa_explorer->params( $path, $method, $source );

				return $params ?: array();
			}
		}

		if ( '/api/subscribers' === $endpoint ) {
			return array(
				array(
					'name'     => 'email',
					'schema'   => array( 'type' => 'string' ),
					'required' => true,
				),
				array(
					'name'   => 'name',
					'schema' => array( 'type' => 'string' ),
				),
				array(
					'name'   => 'status',
					'schema' => array( 'type' => 'string' ),
				),
				array(
					'name'   => 'lists',
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'number' ),
					),
				),
				array(
					'name'   => 'preconfirm_subscriptions',
					'schema' => array( 'type' => 'boolean' ),
				),
				array(
					'name'   => 'attribs',
					'schema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
			);
		}

		return array();
	}
}

Listmonk_Addon::setup();
