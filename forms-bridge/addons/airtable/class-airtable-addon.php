<?php
/**
 * Class Airtable_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FBAPI;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-airtable-form-bridge.php';
require_once 'hooks.php';

/**
 * Airtable addon class.
 */
class Airtable_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'Airtable';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'airtable';

	/**
	 * Handles the addon's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\Airtable_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connection status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$backend = FBAPI::get_backend( $backend );

		if ( ! $backend ) {
			Logger::log( 'Airtable backend ping error: Backend is unkown or invalid', Logger::ERROR );
			return false;
		}

		$response = $backend->get( '/v0/meta/bases' );
		if ( is_wp_error( $response ) ) {
			Logger::log( 'Airtable backend ping error: Unable to list airtable bases', Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend endpoint and retrive the response data.
	 *
	 * @param string $endpoint Airtable endpoint.
	 * @param string $backend Backend name.
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $endpoint, $backend ) {
		$backend = FBAPI::get_backend( $backend );
		if ( ! $backend ) {
			return new WP_Error( 'invalid_backend', 'Backend is unkown or invalid', array( 'backend' => $backend ) );
		}

		if ( $endpoint && '/v0/meta/tables' !== $endpoint ) {
			return $backend->get( $endpoint );
		}

		$response = $backend->get( '/v0/meta/bases' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$tables = array();
		foreach ( $response['data']['bases'] as $base ) {
			$schema_response = $backend->get( "/v0/meta/bases/{$base['id']}/tables" );

			if ( is_wp_error( $schema_response ) ) {
				return $schema_response;
			}

			foreach ( $schema_response['data']['tables'] as $table ) {
				$tables[] = array(
					'base_id'   => $base['id'],
					'base_name' => $base['name'],
					'label'     => "{$base['name']}/{$table['name']}",
					'name'      => $table['name'],
					'id'        => $table['id'],
					'endpoint'  => "/v0/{$base['id']}/{$table['name']}",
				);
			}
		}

		return array( 'data' => array( 'tables' => $tables ) );
	}

	/**
	 * Performs an introspection of the backend API and returns a list of available endpoints.
	 *
	 * @param string      $backend Target backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array|WP_Error
	 */
	public function get_endpoints( $backend, $method = null ) {
		$response = $this->fetch( null, $backend );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$endpoints = array();
		foreach ( $response['data']['tables'] as $table ) {
			$endpoints[] = $table['endpoint'];
		}

		return $endpoints;
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields
	 * and accepted content type.
	 *
	 * @param string      $endpoint Airtable endpoint.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		if ( 'POST' !== $method ) {
			return array();
		}

		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => '__airtable-endpoint-schema',
				'method'   => 'GET',
				'backend'  => $backend,
				'endpoint' => $endpoint,
			)
		);

		$fields = $bridge->get_fields();

		if ( is_wp_error( $fields ) ) {
			return array();
		}

		$api_schema = array();
		foreach ( $fields as $field ) {
			$schema = array();
			switch ( $field['type'] ) {
				case 'number':
					$schema['type'] = 'number';
					break;
				case 'checkbox':
					$schema['type'] = 'boolean';
					break;
				case 'select':
					$schema['type'] = 'string';
					if ( $field['is_multi'] ) {
						$schema['items'] = $schema;
						$schema['type']  = 'array';
					}
					break;
				case 'file':
					$schema['type'] = 'file';
					break;
				case 'textarea':
				default:
					$schema['type'] = 'string';
					break;
			}

			$api_schema[] = array(
				'name'   => $field['name'],
				'schema' => $schema,
			);
		}

		return self::expand_endpoint_schema( $api_schema );
	}
}

Airtable_Addon::setup();
