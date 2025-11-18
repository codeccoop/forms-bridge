<?php
/**
 * Class Mailchimp_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-mailchimp-form-bridge.php';
require_once 'hooks.php';

/**
 * Mapchimp Addon class.
 */
class Mailchimp_Addon extends Addon {

	/**
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Mailchimp';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'mailchimp';

	/**
	 * Holds the addom's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Mailchimp_Form_Bridge';

	/**
	 * Holds the mailchimp marketing API swagger URL.
	 *
	 * @var string
	 */
	public const SWAGGER_URL = 'https://mailchimp.com/developer/spec/marketing.json';


	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Target backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => '__mailchimp-' . time(),
				'endpoint' => '/3.0/lists',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Mailchimp backend ping error response', Logger::ERROR );
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
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => '__mailchimp-' . time(),
				'method'   => 'GET',
				'endpoint' => $endpoint,
				'backend'  => $backend,
			)
		);

		return $bridge->submit();
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields
	 * and accepted content type.
	 *
	 * @param string      $endpoint API endpoint.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		$response = wp_remote_get( self::SWAGGER_URL );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$data        = json_decode( $response['body'], true );
		$oa_explorer = new OpenAPI( $data );

		$method = strtolower( $method ?? 'post' );
		$path   = preg_replace( '/^\/\d+(\.\d+)?/', '', $endpoint );
		$params = $oa_explorer->params( $path, $method );

		return $params;
	}
}

Mailchimp_Addon::setup();
