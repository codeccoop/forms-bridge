<?php
/**
 * Class Slack_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-slack-form-bridge.php';
require_once 'hooks.php';

/**
 * Slack addon class.
 */
class Slack_Addon extends Addon {
	/**
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Slack';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'slack';

	/**
	 * Holds the addon's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Slack_Form_Bridge';

	/**
	 * Holds the OpenAPI Specification URL.
	 *
	 * @var string
	 */
	public const OAS_URL = 'https://raw.githubusercontent.com/slackapi/slack-api-specs/refs/heads/master/web-api/slack_web_openapi_v2_without_examples.json';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Slack_Form_Bridge(
			array(
				'name'     => '__slack-' . time(),
				'endpoint' => '/api/conversations.list',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Slack backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Fetch available models from the OAS spec.
	 *
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array
	 *
	 * @todo Implementar el endpoint de consulta de endpoints disponibles.
	 */
	public function get_endpoints( $backend, $method = null ) {
		$response = wp_remote_get( self::OAS_URL );

		if ( ! is_wp_error( $response ) ) {
			$data = json_decode( $response['body'], true );

			if ( $data ) {
				$oa_explorer = new OpenAPI( $data );
				$paths       = $oa_explorer->paths();

				if ( $method ) {
					$method       = strtolower( $method );
					$method_paths = array();

					foreach ( $paths as $path ) {
						$path_obj = $oa_explorer->path_obj( $path );

						if ( $path_obj && isset( $path_obj[ $method ] ) ) {
							$method_paths[] = $path;
						}
					}

					$paths = $method_paths;
				}

				return array_map(
					function ( $path ) {
						return '/api' . $path;
					},
					$paths,
				);
			}
		}

		return array( '/api/chat.postMessage' );
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields.
	 *
	 * @param string      $endpoint API endpoint.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		$response = wp_remote_get( self::OAS_URL );

		if ( ! is_wp_error( $response ) ) {
			$data = json_decode( $response['body'], true );

			if ( $data ) {
				// phpcs:disable Generic.CodeAnalysis.EmptyStatement
				try {
					$oas_explorer = new OpenAPI( $data );

					$method = strtolower( $method ?? 'post' );
					$path   = preg_replace( '/^\/api/', '', $endpoint );
					$source = in_array( $method, array( 'post', 'put', 'patch' ), true ) ? 'body' : 'query';
					$params = $oas_explorer->params( $path, $method, $source );

					return $params ?: array();
				} catch ( Exception ) {
					// do nothin.
				}
				// phpcs:enable Generic.CodeAnalysis.EmptyStatement
			}
		}

		if ( '/api/chat.postMessage' !== $endpoint ) {
			return array();
		}

		return array(
			array(
				'name'   => 'as_user',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'attachments',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'blocks',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'     => 'channel',
				'schema'   => array( 'type' => 'string' ),
				'required' => true,
			),
			array(
				'name'   => 'icon_emoji',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'icon_url',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'link_names',
				'schema' => array( 'type' => 'boolean' ),
			),
			array(
				'name'   => 'mrkdwn',
				'schema' => array( 'type' => 'boolean' ),
			),
			array(
				'name'   => 'parse',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'reply_broadcast',
				'schema' => array( 'type' => 'boolean' ),
			),
			array(
				'name'   => 'text',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'thread_ts',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'unfurl_links',
				'schema' => array( 'type' => 'boolean' ),
			),
			array(
				'name'   => 'unfurl_media',
				'schema' => array( 'type' => 'boolean' ),
			),
			array(
				'name'   => 'username',
				'schema' => array( 'type' => 'string' ),
			),
		);
	}
}

Slack_Addon::setup();
