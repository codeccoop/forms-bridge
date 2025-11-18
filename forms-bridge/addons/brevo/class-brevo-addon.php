<?php
/**
 * Class Brevo_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-brevo-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * REST API Addon class.
 */
class Brevo_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Brevo';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'brevo';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Brevo_Form_Bridge';

	/**
	 * Holds the OAS URL.
	 *
	 * @var string
	 */
	public const OAS_URL = 'https://developers.brevo.com/reference/getaccount?json=on';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => '__brevo-' . time(),
				'endpoint' => '/v3/contacts/lists',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit( array( 'limit' => 1 ) );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Brevo backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
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
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => '__brevo-' . time(),
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
		$response = wp_remote_get( self::OAS_URL );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$data        = json_decode( $response['body'], true );
		$oa_explorer = new OpenAPI( $data['oasDefinition'] );

		$method = strtolower( $method ?? 'post' );
		$path   = preg_replace( '/^\/v\d+/', '', $endpoint );
		$params = $oa_explorer->params( $path, $method );

		return $params ?: array();
	}
}

Brevo_Addon::setup();
