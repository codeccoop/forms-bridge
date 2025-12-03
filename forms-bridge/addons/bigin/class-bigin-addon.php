<?php
/**
 * Class Bigin_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once FORMS_BRIDGE_ADDONS_DIR . '/zoho/class-zoho-addon.php';

require_once 'class-bigin-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * Bigin Addon class.
 */
class Bigin_Addon extends Zoho_Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Bigin';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'bigin';

	/**
	 * Handles the addon's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Bigin_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge_class = static::BRIDGE;
		$bridge       = new $bridge_class(
			array(
				'name'     => '__bigin-' . time(),
				'backend'  => $backend,
				'endpoint' => '/bigin/v2/users',
				'method'   => 'GET',
			)
		);

		$backend = $bridge->backend;
		if ( ! $backend ) {
			Logger::log( 'Bigin backend ping error: The bridge has no valid backend', Logger::ERROR );
			return false;
		}

		$credential = $backend->credential;
		if ( ! $credential ) {
			Logger::log( 'Bigin backend ping error: The backend has no valid credentials', Logger::ERROR );
			return false;
		}

		$parsed = wp_parse_url( $backend->base_url );
		$host   = $parsed['host'] ?? '';

		if (
			! preg_match(
				'/www\.zohoapis\.(\w{2,3}(\.\w{2})?)$/',
				$host,
				$matches
			)
		) {
			Logger::log( 'Bigin backend ping error: The backend does not points to the zohoapis endpoints', Logger::ERROR );
			return false;
		}

		// $region = $matches[1];
		// if ( ! preg_match( '/' . $region . '$/', $credential->region ) ) {
		// Logger::log( 'Bigin backend ping error: The backend endpoint and the credential region mismatch', Logger::ERROR );
		// return false;
		// }

		$response = $bridge->submit( array( 'type' => 'CurrentUser' ) );
		if ( is_wp_error( $response ) ) {
			Logger::log( 'Bigin backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
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
		if ( ! in_array( $method, array( 'POST', 'PUT' ), true ) ) {
			return array();
		}

		if (
			! preg_match(
				'/\/(([A-Z][a-z]+(_[A-Z][a-z])?)(?:\/upsert)?$)/',
				$endpoint,
				$matches
			)
		) {
			return array();
		}

		$module = $matches[2];

		$bridge_class = self::BRIDGE;
		$bridge       = new $bridge_class(
			array(
				'name'     => '__bigin-' . time(),
				'backend'  => $backend,
				'endpoint' => '/bigin/v2/settings/layouts',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit( array( 'module' => $module ) );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$fields = array();
		foreach ( $response['data']['layouts'] as $layout ) {
			foreach ( $layout['sections'] as $section ) {
				foreach ( $section['fields'] as $field ) {
					$type = $field['json_type'];
					if ( 'jsonobject' === $type ) {
						$type = 'object';
					} elseif ( 'jsonarray' === $type ) {
						$type = 'array';
					} elseif ( 'double' === $type ) {
						$type = 'number';
					}

					$fields[] = array(
						'name'   => $field['api_name'],
						'schema' => array( 'type' => $type ),
					);
				}
			}
		}

		return $fields;
	}
}

Bigin_Addon::setup();
