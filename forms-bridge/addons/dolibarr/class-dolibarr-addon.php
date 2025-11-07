<?php

namespace FORMS_BRIDGE;

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
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'Dolibarr';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'dolibarr';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\Dolibarr_Form_Bridge';

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
			return false;
		}

		$code = $response['data']['success']['code'] ?? null;
		return 200 === $code;
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
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => '__dolibarr-' . time(),
				'endpoint' => $endpoint,
				'backend'  => $backend,
				'method'   => 'GET',
			)
		);

		return $bridge->submit();
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields
	 * and accepted content type.
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $backend Backend name.
	 *
	 * @return array
	 */
	public function get_endpoint_schema( $endpoint, $backend ) {
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => '__dolibarr-' . time(),
				'endpoint' => $endpoint,
				'backend'  => $backend,
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit( array( 'limit' => 1 ) );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$entry = $response['data'][0] ?? null;
		if ( ! $entry ) {
			return array();
		}

		$fields = array();
		foreach ( $entry as $field => $value ) {
			if ( wp_is_numeric_array( $value ) ) {
				$type = 'array';
			} elseif ( is_array( $value ) ) {
				$type = 'object';
			} elseif ( is_double( $value ) ) {
				$type = 'number';
			} elseif ( is_int( $value ) ) {
				$type = 'integer';
			} else {
				$type = 'string';
			}

			$fields[] = array(
				'name'   => $field,
				'schema' => array( 'type' => $type ),
			);
		}

		return $fields;
	}
}

Dolibarr_Addon::setup();
