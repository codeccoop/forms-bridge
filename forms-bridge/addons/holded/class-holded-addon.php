<?php
/**
 * Class Holded_Addon
 *
 * @package formsbridge
 */

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
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Holded';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'holded';

	/**
	 * Holds the addom's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Holded_Form_Bridge';

	/**
	 * Holds the OAS endpoints base URL.
	 *
	 * @var string
	 */
	public const OAS_BASE_URL = 'https://developers.holded.com/holded/api-next';

	/**
	 * Holds the addon OAS URLs map.
	 *
	 * @var array
	 */
	public const OAS_URLS = array(
		'invoicing'  => '/v2/branches/1.0/reference/list-contacts-1',
		'crm'        => '/v2/branches/1.0/reference/list-leads-1',
		'projects'   => '/v2/branches/1.0/reference/list-projects',
		'team'       => '/v2/branches/1.0/reference/listemployees',
		'accounting' => '/v2/branches/1.0/reference/listdailyledger',
	);

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

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Holded backend ping error response', Logger::ERROR );
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
		if ( 'api' !== $api_base ) {
			array_unshift( $chunks, 'api' );
		}

		[, $module, $version, $resource] = $chunks;

		$oas_url = self::OAS_URLS[ $module ] ?? null;
		if ( ! $oas_url ) {
			return array();
		}

		$oas_url  = self::OAS_BASE_URL . $oas_url . '?dereference=false&reduce=false';
		$response = wp_remote_get( $oas_url );
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$data        = json_decode( $response['body'], true );
		$oa_explorer = new OpenAPI( $data['data']['api']['schema'] );

		$method = strtolower( $method ?? 'post' );
		$path   = preg_replace( '/\/api\/' . $module . '\/v\d+/', '', $endpoint );
		$params = $oa_explorer->params( $path, $method );

		return $params ?: array();
	}
}

Holded_Addon::setup();
