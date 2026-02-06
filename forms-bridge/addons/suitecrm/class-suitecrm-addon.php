<?php
/**
 * Class SuiteCRM_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-suitecrm-form-bridge.php';
require_once 'hooks.php';

/**
 * SuiteCRM Addon class.
 *
 * Provides integration with SuiteCRM v4_1 REST API.
 */
class SuiteCRM_Addon extends Addon {

	/**
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'SuiteCRM';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'suitecrm';

	/**
	 * Holds the addon's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\SuiteCRM_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Target backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => '__suitecrm-' . time(),
				'method'   => 'get_user_id',
				'endpoint' => '',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			Logger::log( 'SuiteCRM backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend module and retrieve the response data.
	 *
	 * @param string $endpoint Target module name.
	 * @param string $backend Target backend name.
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $endpoint, $backend ) {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => '__suitecrm-' . time(),
				'method'   => 'get_entry_list',
				'endpoint' => $endpoint,
				'backend'  => $backend,
			)
		);

		return $bridge->submit( array( 'max_results' => 100 ) );
	}

	/**
	 * Fetch available modules from the backend.
	 *
	 * @param string      $backend Backend name.
	 * @param string|null $method API method.
	 *
	 * @return array
	 */
	public function get_endpoints( $backend, $method = null ) {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => '__suitecrm-' . time(),
				'method'   => 'get_available_modules',
				'endpoint' => '',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			return array();
		}

		if ( ! isset( $response['data']['modules'] ) ) {
			return array();
		}

		return array_map(
			function ( $module ) {
				return $module['module_key'];
			},
			$response['data']['modules']
		);
	}

	/**
	 * Performs an introspection of the backend module and returns API fields
	 * and accepted content type.
	 *
	 * @param string      $module Target module name.
	 * @param string      $backend Target backend name.
	 * @param string|null $method API method.
	 *
	 * @return array List of fields and content type of the module.
	 */
	public function get_endpoint_schema( $module, $backend, $method = null ) {
		if ( 'set_entry' !== $method ) {
			return array();
		}

		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => '__suitecrm-' . time(),
				'method'   => 'get_module_fields',
				'endpoint' => $module,
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			return array();
		}

		if ( ! isset( $response['data']['module_fields'] ) ) {
			return array();
		}

		$fields = array();
		foreach ( $response['data']['module_fields'] as $name => $spec ) {
			if ( $spec['calculated'] ?? false ) {
				continue;
			}

			$type = 'string';

			if (
				in_array( $spec['type'], array( 'id', 'int', 'integer', 'relate', 'parent' ), true )
				|| ! empty( $spec['id_name'] )
			) {
				$type = 'integer';
			} elseif ( in_array( $spec['type'], array( 'decimal', 'float', 'currency' ), true ) ) {
				$type = 'number';
			} elseif ( 'bool' === $spec['type'] ) {
				$type = 'boolean';
			}

			$schema = array(
				'type'     => $type,
				'required' => ! empty( $spec['required'] ),
			);

			$fields[] = array(
				'name'   => $name,
				'schema' => $schema,
			);
		}

		return $fields;
	}
}

SuiteCRM_Addon::setup();
