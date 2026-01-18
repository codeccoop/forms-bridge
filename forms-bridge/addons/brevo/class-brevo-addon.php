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
	public const OAS_URL = 'https://developers.brevo.com/reference/get_companies?json=on';

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
		$response = wp_remote_get(
			self::OAS_URL,
			array(
				'headers' => array(
					'Accept'     => 'application/json',
					'Host'       => 'developers.brevo.com',
					'Referer'    => 'https://developers.brevo.com/reference/get_companies',
					'Alt-Used'   => 'developers.brevo.com',
					'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$data        = json_decode( $response['body'], true );
		$oa_explorer = new OpenAPI( $data['oasDefinition'] );

		$paths = $oa_explorer->paths();

		if ( $method ) {
			$method_paths = array();
			foreach ( $paths as $path ) {
				$path_obj = $oa_explorer->path_obj( $path );

				if ( $path_obj && isset( $path_obj[ strtolower( $method ) ] ) ) {
					$method_paths[] = $path;
				}
			}

			$paths = $method_paths;
		}

		return array_map(
			function ( $path ) {
				return '/v3' . $path;
			},
			$paths,
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
		$bytes    = random_bytes( 16 );
		$bytes[6] = chr( ord( $bytes[6] ) & 0x0f | 0x40 );
		$bytes[8] = chr( ord( $bytes[8] ) & 0x3f | 0x80 );
		$uuid     = vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $bytes ), 4 ) );

		$response = wp_remote_get(
			self::OAS_URL,
			array(
				'headers' => array(
					'Accept'           => 'application/json',
					'Host'             => 'developers.brevo.com',
					'Referer'          => 'https://developers.brevo.com/reference/get_companies',
					'Alt-Used'         => 'developers.brevo.com',
					'User-Agent'       => 'Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0',
					'X-Requested-With' => 'XMLHttpRequest',
				),
				'cookies' => array(
					'anonymous_id'    => $uuid,
					'first_referrer'  => 'https://app.brevo.com/',
					'pscd'            => 'get.brevo.com',
					'readme_language' => 'shell',
					'readme_library'  => '{%22shell%22:%22curl%22}',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$data = json_decode( $response['body'], true );
		if ( ! $data ) {
			return array();
		}

		$oa_explorer = new OpenAPI( $data['oasDefinition'] );

		$method = strtolower( $method ?? 'post' );
		$path   = preg_replace( '/^\/v\d+/', '', $endpoint );
		$source = in_array( $method, array( 'post', 'put', 'patch' ), true ) ? 'body' : 'query';
		$params = $oa_explorer->params( $path, $method, $source );

		return $params ?: array();
	}
}

Brevo_Addon::setup();
