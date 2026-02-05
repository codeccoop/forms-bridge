<?php
/**
 * Class Dolibarr_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FORMS_BRIDGE\OpenAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-dolibarr-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * Dolibarr Addon class.
 */
class Dolibarr_Addon extends Addon {

	/**
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Dolibarr';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'dolibarr';

	/**
	 * Holds the addom's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Dolibarr_Form_Bridge';

	/**
	 * Holds the Dolibarr's REST API swagger.json endpoint.
	 *
	 * @var string
	 */
	public const SWAGGER_ENDPOINT = '/api/index.php/explorer/swagger.json';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Target backend name.
	 *
	 * @return boolean|WP_Error
	 */
	public function ping( $backend ) {
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => '__dolibarr-' . time(),
				'endpoint' => '/api/index.php/status',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();
		if ( is_wp_error( $response ) ) {
			Logger::log( 'Dolibarr backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		$code = $response['data']['success']['code'] ?? null;

		if ( 200 !== $code ) {
			Logger::log( 'Dolibarr backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
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
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => '__dolibarr-' . time(),
				'endpoint' => self::SWAGGER_ENDPOINT,
				'backend'  => $backend,
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$version = $response['data']['swagger'] ?? null;
		if ( ! $version ) {
			return array();
		}

		$oa_explorer = new OpenAPI( $response['data'] );

		$path   = preg_replace( '/.*\/api\/index.php/', '', $endpoint );
		$method = strtolower( $method ?? 'post' );
		$params = $oa_explorer->params( $path, $method );
		if ( empty( $params ) ) {
			return array();
		}

		$response = $bridge->patch( array( 'endpoint' => $endpoint ) )
			->submit( array( 'limit' => '1' ) );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$entry = $response['data'][0] ?? null;
		if ( ! $entry ) {
			return array();
		}

		$fields = array();
		foreach ( $entry as $field => $value ) {
			$fields[] = array(
				'name'   => $field,
				'schema' => $this->get_value_schema( $value ),
			);
		}

		return self::expand_endpoint_schema( $fields );
	}

	/**
	 * Return the json schema of a given value.
	 *
	 * @param mixed $value Target value.
	 *
	 * @return array
	 */
	private function get_value_schema( $value ) {
		$schema = array();
		// phpcs:disable Universal.Operators.StrictComparisons
		if ( wp_is_numeric_array( $value ) ) {
			$schema['type'] = 'array';

			if ( count( $value ) ) {
				$schema['items'] = $this->get_value_schema( $value[0] );
			} else {
				$schema['items'] = array( 'type' => 'string' );
			}
		} elseif ( is_array( $value ) ) {
			$schema['type'] = 'object';

			foreach ( $value as $name => $val ) {
				$schema['properties'][ $name ] = $this->get_value_schema( $val );
			}
		} elseif ( is_double( $value ) ) {
			$schema['type'] = 'number';
		} elseif ( intval( $value ) == $value ) {
			$schema['type'] = 'integer';
		} else {
			$schema['type'] = 'string';
		}
		// phpcs:enable

		return $schema;
	}

	/**
	 * Performs an introspection of the backend API and returns a list of available endpoints.
	 *
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array|WP_Error
	 */
	public function get_endpoints( $backend, $method = null ) {
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => '__dolibarr-' . time(),
				'endpoint' => self::SWAGGER_ENDPOINT,
				'backend'  => $backend,
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$version = $response['data']['swagger'] ?? null;
		if ( ! $version ) {
			return array();
		}

		$oa_explorer = new OpenAPI( $response['data'] );

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

		return array_map(
			function ( $path ) {
				return '/api/index.php' . $path;
			},
			$paths,
		);
	}
}

Dolibarr_Addon::setup();
