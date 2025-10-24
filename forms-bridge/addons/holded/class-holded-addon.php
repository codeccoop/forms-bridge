<?php

namespace FORMS_BRIDGE;

use TypeError;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-holded-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * REST API Addon class.
 */
class Holded_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	public const title = 'Holded';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	public const name = 'holded';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	public const bridge_class = '\FORMS_BRIDGE\Holded_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => '__holded-' . time(),
				'endpoint' => '/api/invoicing/v1/contacts',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit( array( 'limit' => 1 ) );
		return ! is_wp_error( $response );
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
		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => '__holded-' . time(),
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
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend ) {
		$chunks = array_values( array_filter( explode( '/', $endpoint ) ) );
		if ( empty( $chunks ) ) {
			return array();
		}

		$api_base = $chunks[0];
		if ( $api_base !== 'api' ) {
			array_unshift( $chunks, 'api' );
		}

		[, $module, $version, $resource] = $chunks;

		if (
			! in_array(
				$module,
				array(
					'invoicing',
					'crm',
					'projects',
					'team',
					'accounting',
				)
			) ||
			$version !== 'v1'
		) {
			return array();
		}

		$path = plugin_dir_path( __FILE__ ) . "/data/swagger/{$module}.json";
		if ( ! is_file( $path ) ) {
			return array();
		}

		$file_content = file_get_contents( $path );
		try {
			$paths = json_decode( $file_content, true );
		} catch ( TypeError ) {
			return array();
		}

		$path = '/' . $resource;
		if ( $resource === 'documents' ) {
			$path .= '/{docType}';
		}

		if ( ! isset( $paths[ $path ] ) ) {
			return array();
		}

		$schema = $paths[ $path ];
		if ( ! isset( $schema['post'] ) ) {
			return array();
		}

		$schema = $schema['post'];

		$fields = array();
		if ( isset( $schema['parameters'] ) ) {
			foreach ( $schema['parameters'] as $param ) {
				$fields[] = array(
					'name'   => $param['name'],
					'schema' => $param['schema'],
				);
			}
		} elseif (
			isset(
				$schema['requestBody']['content']['application/json']['schema']['properties']
			)
		) {
			$properties =
				$schema['requestBody']['content']['application/json']['schema']['properties'];
			foreach ( $properties as $name => $schema ) {
				$fields[] = array(
					'name'   => $name,
					'schema' => $schema,
				);
			}
		}

		return $fields;
	}
}

Holded_Addon::setup();
