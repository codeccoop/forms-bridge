<?php
/**
 * Class Zulip_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-zulip-form-bridge.php';
require_once 'hooks.php';

/**
 * Zulip addon class
 */
class Zulip_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Zulip';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'zulip';

	/**
	 * Handles the addon's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Zulip_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Zulip_Form_Bridge(
			array(
				'name'     => '__zulip-' . time(),
				'endpoint' => '/api/v1/users',
				'method'   => 'GET',
				'backend'  => $backend,
			),
			'zulip'
		);

		$response = $bridge->submit();
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
		$bridge = new Zulip_Form_Bridge(
			array(
				'name'     => '__zulip-' . time(),
				'endpoint' => $endpoint,
				'method'   => 'GET',
				'backend'  => $backend,
			),
			'zulip'
		);

		return $bridge->submit();
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields.
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $backend Backend name.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend ) {
		$path    = plugin_dir_path( __FILE__ ) . 'data/openapi.json';
		$openapi = OpenAPI::from( $path );
		return $openapi->params( $endpoint ) ?: array();
	}
}

Zulip_Addon::setup();
