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
		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => '__airtable-' . time(),
				'backend'  => $backend,
				'endpoint' => '/v0/meta/bases',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();
		if ( is_wp_error( $response ) ) {
			Logger::log( 'Airtable backend ping error: Unable to recover the credential access token', Logger::ERROR );
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
		$endpoints = $this->get_endpoints( $backend );

		if ( is_wp_error( $endpoint ) ) {
			return $endpoint;
		}

		$response = array(
			'data' => array( 'tables' => array() ),
		);

		foreach ( $endpoints as $endpoint ) {
			list( $base_id, $table_name ) = array_slice( explode( '/', $endpoint ), 2 );

			$response['data']['tables'][] = array(
				'endpoint' => $endpoint,
				'name'     => $table_name,
				'base'     => $base_id,
			);
		}

		return $response;
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
		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => '__airtable-endpoints',
				'backend'  => $backend,
				'endpoint' => '/v0/meta/bases',
				'method'   => 'GET',
			),
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) || empty( $response['data']['bases'] ) ) {
			return array();
		}

		$endpoints = array();
		foreach ( $response['data']['bases'] as $base ) {
			$response = $bridge->patch( array( 'endpoint' => "/v0/meta/bases/{$base['id']}/tables" ) )
				->submit();

			if ( is_wp_error( $response ) ) {
				break;
			}

			foreach ( $response['data']['tables'] as $table ) {
				$endpoints[] = "/v0/{$base['id']}/{$table['name']}";
			}
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

		$schema = array();
		foreach ( $fields as $field ) {
			if (
				in_array(
					$field['type'],
					array(
						'aiText',
						'formula',
						'autoNumber',
						'button',
						'count',
						'createdBy',
						'createdTime',
						'lastModifiedBy',
						'lastModifiedTime',
						'rollup',
						'externalSyncSource',
						'multipleAttachments',
					),
					true,
				)
			) {
				continue;
			}

			switch ( $field['type'] ) {
				case 'rating':
				case 'number':
					$type = 'number';
					break;
				case 'checkbox':
					$type = 'boolean';
					break;
				case 'multipleSelects':
				case 'multipleCollaborators':
				case 'multipleLookupValues':
				case 'multipleRecordLinks':
					$type = 'array';
					break;
				default:
					$type = 'string';
					break;
			}

			$schema[] = array(
				'name'   => $field['name'],
				'schema' => array( 'type' => $type ),
			);
		}

		return $schema;
	}
}

Airtable_Addon::setup();
