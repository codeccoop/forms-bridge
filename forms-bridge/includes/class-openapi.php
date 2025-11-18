<?php
/**
 * Class OpenAPI
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use Error;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * OpenAPI data explorer.
 */
class OpenAPI {

	/**
	 * Holds available HTTP methods.
	 *
	 * @var array<string>
	 */
	public const METHODS = array( 'get', 'post', 'put', 'patch', 'delete', 'trace' );

	/**
	 * Holds the swagger data.
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Handles the standard version of the data.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Return an OpenAPI parser instance from a filepath to a swagger file.
	 *
	 * @param string $path File path.
	 *
	 * @return OpenAPI|null
	 */
	public static function from( $path ) {
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return;
		}

		$content = file_get_contents( $path );

		try {
			$data = json_decode( $content, true, JSON_THROW_ON_ERROR );
		} catch ( Error ) {
			return;
		}

		return new OpenAPI( $data );
	}

	/**
	 * Class constructor.
	 *
	 * @param array $data Swagger data.
	 *
	 * @throws Exception In case data is not a valid swagger condiguration.
	 */
	public function __construct( $data ) {
		if ( ! isset( $data['openapi'] ) && ! isset( $data['swagger'] ) ) {
			throw new Exception( 'Invalid OpenAPI data' );
		}

		$this->data    = $data;
		$this->version = $data['openapi'] ?? $data['swagger'];
	}

	/**
	 * Returns the available list of API endpoints or swagger paths.
	 *
	 * @return array
	 */
	public function paths() {
		return array_keys( $this->data['paths'] );
	}

	/**
	 * Search for a path object in the swagger data.
	 *
	 * @param string $path Target path.
	 *
	 * @return array|null
	 */
	public function path_obj( $path ) {
		$path = self::parse_path( $path );

		foreach ( $this->data['paths'] as $name => $obj ) {
			if ( preg_match_all( '/{([^}]+)}/', $name, $matches ) ) {
				$regexp = $name;

				foreach ( $matches[0] as $match ) {
					$regexp = str_replace( $match, '[^\/]+', $regexp );
				}

				if ( preg_match( '#^' . $regexp . '$#', $path ) ) {
					return $obj;
				}
			} elseif ( $path === $name ) {
				return $obj;
			}
		}
	}

	/**
	 * Retrives allowed content type for a path and an HTTP method.
	 *
	 * @param string $path Target path.
	 * @param string $method HTTP method.
	 *
	 * @return array|null
	 */
	public function encoding( $path, $method ) {
		$path    = $this->path_obj( $path );
		$content = $path[ $method ]['requestBody']['content'] ?? null;

		if ( ! $content ) {
			return;
		}

		return array_keys( $content );
	}

	/**
	 * Retrives params for a path and an HTTP method. Optionally, filtered by the
	 * param source.
	 *
	 * @param string       $path Target path.
	 * @param string       $method HTTP method.
	 * @param string|array $source Param source or sources. It could be body, path, query or cookie.
	 *
	 * @return array|null
	 */
	public function params( $path, $method = null, $source = null ) {
		$path = self::parse_path( $path );

		$path_obj = $this->path_obj( $path );
		if ( ! $path_obj ) {
			return;
		}

		$parameters = $path_obj['parameters'] ?? null;
		if ( ! $parameters ) {
			if ( ! $method ) {
				return;
			}

			$parameters = array();
		}

		foreach ( $parameters as &$param ) {
			$param['in'] = 'path';
		}

		$method_obj = $path_obj[ $method ] ?? null;
		if ( $method && ! $method_obj ) {
			return;
		}

		$parameters = array_merge(
			$parameters,
			$method_obj['parameters'] ?? array()
		);

		$c = count( $parameters );
		for ( $i = 0; $i < $c; $i++ ) {
			$param = &$parameters[ $i ];
			if ( 'body' === $param['in'] && isset( $param['schema'] ) ) {
				if ( isset( $param['schema']['$ref'] ) ) {
					$param['schema'] = $this->get_ref( $param['schema']['$ref'] );
				}

				if ( isset( $param['schema']['properties'] ) && is_array( $param['schema']['properties'] ) ) {
					array_splice( $parameters, $i, 1 );

					foreach ( $param['schema']['properties'] as $prop => $prop_schema ) {
						$parameters[] = array_merge(
							$prop_schema,
							array(
								'name' => $prop,
								'in'   => 'body',
							),
						);
					}
				}
			}
		}

		$body = $method_obj['requestBody'] ?? null;
		if ( $body ) {
			$parameters = array_merge( $parameters, $this->body_to_params( $body ) );
		}

		if ( $source ) {
			$parameters = array_values(
				array_filter(
					$parameters,
					function ( $param ) use ( $source ) {
						$in = $param['in'] ?? null;

						if ( is_array( $source ) ) {
							return in_array( $in, $source, true );
						}

						return $in === $source;
					}
				)
			);
		}

		$l = count( $parameters );
		for ( $i = 0; $i < $l; $i++ ) {
			$param = &$parameters[ $i ];

			if ( isset( $param['type'] ) && ! isset( $param['schema'] ) ) {
				$param['schema'] = array( 'type' => $param['type'] );
				unset( $param['type'] );
			}

			if ( isset( $param['$ref'] ) ) {
				$parameters[ $i ] = $this->get_ref( $param['$ref'] );
			} elseif ( isset( $param['schema']['$ref'] ) ) {
				$param['schema'] = $this->get_ref( $param['schema']['$ref'] );
			}
		}

		return $parameters;
	}

	/**
	 * Retrives the value of a ref in the swagger data.
	 *
	 * @param string $ref Ref absolute path.
	 *
	 * @return mixed|null
	 */
	public function get_ref( $ref ) {
		if ( ! str_starts_with( $ref, '#/' ) ) {
			return;
		}

		$pointer = str_replace( '/', '.', substr( $ref, 2 ) );

		if ( ! JSON_Finger::validate( $pointer ) ) {
			return;
		}

		$finger = new JSON_Finger( $this->data );
		return $finger->get( $pointer );
	}

	/**
	 * Scans a body object and return its attributes as an array of parameters.
	 *
	 * @param array $body Body object.
	 *
	 * @return array
	 */
	private function body_to_params( $body ) {
		$parameters = array();

		foreach ( $body['content'] as $encoding => $obj ) {
			if ( isset( $obj['schema']['$ref'] ) ) {
				$obj['schema'] = $this->get_ref( $obj['schema']['$ref'] );
			}

			foreach ( $obj['schema']['properties'] as $name => $defn ) {
				$parameters[] = array_merge(
					array(
						'name'     => $name,
						'encoding' => $encoding,
						'in'       => 'body',
					),
					$defn
				);
			}
		}

		return $parameters;
	}

	/**
	 * URL path normalizer.
	 *
	 * @param string $path Target path.
	 *
	 * @return string Normalized path.
	 */
	private static function parse_path( $path ) {
		$url = wp_parse_url( $path );
		if ( empty( $url['path'] ) ) {
			return '/';
		} else {
			$path = $url['path'];
		}

		$path = strpos( $path, '/' ) !== 0 ? '/' . $path : $path;
		return preg_replace( '/\/+$/', '', $path );
	}
}
