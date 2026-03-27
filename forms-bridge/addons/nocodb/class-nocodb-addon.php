<?php
/**
 * Class NocoDB_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FBAPI;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-nocodb-form-bridge.php';
require_once 'hooks.php';

/**
 * NocoDB addon class.
 */
class NocoDB_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'NocoDB';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'nocodb';

	/**
	 * Handles the addon's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\NocoDB_Form_Bridge';

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
			Logger::log( 'NocoDB backend ping error: Backend is unkown or invalid', Logger::ERROR );
			return false;
		}

		$response = $backend->get( '/api/orgs' );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'NocoDB backend ping error: Unable to list grist organizations', Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend endpoint and retrive the response data.
	 *
	 * @param string $endpoint NocoDB endpoint.
	 * @param string $backend Backend name.
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $endpoint, $backend ) {
		$backend = FBAPI::get_backend( $backend );
		if ( ! $backend ) {
			return new WP_Error( 'invalid_backend', 'Backend is unkown or invalid', array( 'backend' => $backend ) );
		}

		if ( $endpoint && '/api/orgs/{orgId}/tables' !== $endpoint ) {
			return $backend->get( $endpoint );
		}

		if ( preg_match( '/[^\/]+(?=\.getgrist.com)/', $backend->base_url, $matches ) ) {
			$org_id = $matches[0];
		}

		if ( ! isset( $org_id ) ) {
			foreach ( $backend->headers as $header => $value ) {
				if ( 'orgid' === strtolower( $header ) ) {
					$org_id = $value;
					break;
				}
			}
		}

		if ( ! isset( $org_id ) ) {
			return new WP_Error( 'invalid_backend', 'Backend does not have the orgId header', $backend->data() );
		}

		$response = $backend->get( "/api/orgs/{$org_id}/workspaces" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$tables = array();
		foreach ( $response['data'] as $workspace ) {
			if ( $workspace['isSupportWorkspace'] ?? false ) {
				continue;
			}

			foreach ( $workspace['docs'] as $doc ) {
				$docs_response = $backend->get( "/api/docs/{$doc['id']}/tables" );

				if ( is_wp_error( $docs_response ) ) {
					continue;
				}

				foreach ( $docs_response['data']['tables'] as $table ) {
					$tables[] = array(
						'org_id'   => $org_id,
						'doc_id'   => $doc['urlId'],
						'doc_name' => $doc['name'],
						'id'       => $table['id'],
						'label'    => "{$doc['name']}/{$table['id']}",
						'endpoint' => "/api/docs/{$doc['urlId']}/tables/{$table['id']}/records",
					);
				}
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
	 * @param string      $endpoint NocoDB endpoint.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		if ( 'POST' !== $method ) {
			return array();
		}

		$bridge = new NocoDB_Form_Bridge(
			array(
				'name'     => '__nocodb-endpoint-introspection',
				'backend'  => $backend,
				'endpoint' => $endpoint,
				'method'   => 'GET',
			)
		);

		if ( ! $bridge->is_valid ) {
			return array();
		}

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
				default:
					$schema['type'] = 'string';
					break;
			}

			$api_schema[] = array(
				'name'   => $field['name'],
				'schema' => $schema,
			);
		}

		return OpenAPI::expand_fields_schema( $api_schema );
	}

	/**
	 * Gets expiration time for introspection cache based on the introspection
	 * method.
	 *
	 * @param string $method Introspection method (ping, endpoints, schema).
	 *
	 * @return int Time in seconds.
	 */
	public function introspection_cache_expiration( $method ) {
		if ( Logger::is_active() ) {
			return 0;
		}

		switch ( $method ) {
			case 'ping':
				return 60 * 60 * 24;
			case 'endpoints':
				return 60 * 10;
			case 'schema':
				return 60 * 5;
			default:
				return 0;
		}
	}
}

NocoDB_Addon::setup();
