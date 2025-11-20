<?php
/**
 * Class Zoho_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-zoho-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * Zoho Addon class.
 */
class Zoho_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Zoho';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'zoho';

	/**
	 * Handles the addon's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Zoho_Form_Bridge';

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
				'name'     => '__zoho-' . time(),
				'backend'  => $backend,
				'endpoint' => '/crm/v7/users',
				'method'   => 'GET',
			)
		);

		$backend = $bridge->backend;
		if ( ! $backend ) {
			Logger::log( 'Zoho backend ping error: Bridge has no valid backend', Logger::ERROR );
			return false;
		}

		$credential = $backend->credential;
		if ( ! $credential ) {
			Logger::log( 'Zoho backend ping error: Backend has no valid credential', Logger::ERROR );
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
			Logger::log( 'Zoho backend ping error: Backend does not point to the zohoapis endpoints', Logger::ERROR );
			return false;
		}

		// $region = $matches[1];
		// if (!preg_match('/' . $region . '$/', $credential->region)) {
		// Logger::log( 'Bigin backend ping error: The backend endpoint and the credential region mismatch', Logger::ERROR );
		// return false;
		// }

		$response = $bridge->submit( array( 'type' => 'CurrentUser' ) );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Zoho backend ping error response', Logger::ERROR );
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
		if ( in_array( $method, array( 'POST', 'PUT' ), true ) ) {
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

		$bridge_class = static::BRIDGE;
		$bridge       = new $bridge_class(
			array(
				'name'     => '__zoho-' . time(),
				'backend'  => $backend,
				'endpoint' => '/crm/v7/settings/layouts',
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

Zoho_Addon::setup();
