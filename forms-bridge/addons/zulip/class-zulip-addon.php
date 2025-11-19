<?php
/**
 * Class Zulip_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use Exception;

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
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Zulip';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'zulip';

	/**
	 * Holds the addon's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Zulip_Form_Bridge';

	/**
	 * Holds the Zulip OAS public URL.
	 *
	 * @var string
	 */
	public const OAS_URL = 'https://raw.githubusercontent.com/zulip/zulip/refs/heads/main/zerver/openapi/zulip.yaml';

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
				'endpoint' => '/api/v1/streams',
				'method'   => 'GET',
				'backend'  => $backend,
			),
			'zulip'
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Zulip backend ping error response', Logger::ERROR );
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
	 * @param string      $endpoint API endpoint.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the endpoint.
	 *
	 * @todo fix not found oas url.
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		if ( function_exists( 'yaml_parse' ) ) {
			$response = wp_remote_get( self::OAS_URL );

			if ( ! is_wp_error( $response ) ) {
				$data = yaml_parse( $response['body'] );

				if ( $data ) {
					try {
						$oa_explorer = new OpenAPI( $data );

						$method = strtolower( $method ?? 'post' );
						$path   = preg_replace( '', '', $endpoint );
						$source = in_array( $method, array( 'post', 'put', 'patch' ), true ) ? 'body' : 'query';
						$params = $oa_explorer->params( $path, $method, $source );

						return $params ?: array();
					} catch ( Exception ) {
						// do nothing.
					}
				}
			}
		}

		if ( '/api/v1/messages' !== $endpoint ) {
			return array();
		}

		return array(
			array(
				'name'     => 'type',
				'schema'   => array(
					'type' => 'string',
					'enum' => array( 'direct', 'stream' ),
				),
				'required' => true,
			),
			array(
				'name'     => 'to',
				'schema'   => array(
					'type'  => 'array',
					'items' => array(
						'type' => array( 'string', 'integer' ),
					),
				),
				'required' => true,
			),
			array(
				'name'     => 'content',
				'schema'   => array( 'type' => 'string' ),
				'required' => true,
			),
			array(
				'name'   => 'topic',
				'schema' => array(
					'type'    => 'string',
					'default' => '(no topic)',
				),
			),
			array(
				'name'   => 'queue_id',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'local_id',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'read_by_sender',
				'schema' => array( 'type' => 'boolean' ),
			),
		);
	}
}

Zulip_Addon::setup();
