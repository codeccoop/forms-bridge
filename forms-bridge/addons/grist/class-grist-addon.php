<?php
/**
 * Class Grist_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FBAPI;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-grist-form-bridge.php';
require_once 'hooks.php';

/**
 * Grist addon class.
 */
class Grist_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'Grist';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'grist';

	/**
	 * Handles the addon's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\Grist_Form_Bridge';

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
			Logger::log( 'Grist backend ping error: Backend is unkown or invalid', Logger::ERROR );
			return false;
		}

		$response = $backend->get( '/api/orgs' );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Grist backend ping error: Unable to list grist organizations', Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend endpoint and retrive the response data.
	 *
	 * @param string $endpoint Grist endpoint.
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
	 * @param string      $endpoint Grist endpoint.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		if ( 'POST' !== $method ) {
			return array();
		}

		$bridge = new Grist_Form_Bridge(
			array(
				'name'     => '__grist-endpoint-introspection',
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

		$schema = array();
		foreach ( $fields as $field ) {
			switch ( $field['type'] ) {
				case 'number':
					$type = 'number';
					break;
				case 'checkbox':
					$type = 'boolean';
					break;
				case 'select':
					$type = $field['is_multi'] ? 'array' : 'string';
					break;
				case 'file':
					$type = 'file';
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

Grist_Addon::setup();
