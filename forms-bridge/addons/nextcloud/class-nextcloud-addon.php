<?php
/**
 * Class Nextcloud_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FBAPI;
use SimpleXMLElement;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-nextcloud-form-bridge.php';
require_once 'hooks.php';

/**
 * Nextcloud Addon class.
 */
class Nextcloud_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'Nextcloud';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'nextcloud';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\Nextcloud_Form_Bridge';

	/**
	 * Addon loader. Set up hooks to skip payload prunes if it comes from a
	 * nextcloud bridge.
	 */
	public function load() {
		parent::load();

		add_filter(
			'forms_bridge_prune_empties',
			static function ( $prune, $bridge ) {
				if ( 'nextcloud' === $bridge->addon ) {
					return false;
				}

				return $prune;
			},
			5,
			2
		);
	}

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Target backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$backend = FBAPI::get_backend( $backend );

		if ( ! $backend ) {
			return false;
		}

		$credential = $backend->credential;
		if ( ! $credential || 'Basic' !== $credential->schema ) {
			return false;
		}

		$response = $backend->get( '/remote.php/dav/files/' . rawurlencode( $credential->client_id ) );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Nextcloud backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend model and retrive the response data.
	 *
	 * @param string $endpoint Target model name.
	 * @param string $backend Target backend name.
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $endpoint, $backend ) {
		if ( ! class_exists( 'SimpleXMLElement' ) ) {
			return new WP_Error( 'xml_not_supported', 'Requires phpxml extension to be enabled' );
		}

		$backend = FBAPI::get_backend( $backend );
		if ( ! $backend ) {
			return new WP_Error( 'invalid_backend', 'Backend not found', array( 'backend' => $backend ) );
		}

		$credential = $backend->credential;
		if ( ! $credential || 'Basic' !== $credential->schema ) {
			return new WP_Error( 'invalid_backend', 'Backend has no credential', $backend->data() );
		}

		$authorization = $credential->authorization();
		if ( ! $authorization ) {
			return new WP_Error( 'invalid_credential', 'Credential has no authorization', $credential->data() );
		}

		$url = $backend->url( '/remote.php/dav/files/' . rawurlencode( $credential->client_id ) );

		$response = wp_remote_request(
			$url,
			array(
				'method'  => 'PROPFIND',
				'headers' => array(
					'Depth'         => '5',
					'Authorization' => $authorization,
					'Content-Type'  => 'text/xml',
				),
				'body'    => '<?xml version="1.0" encoding="utf-8" ?>'
					. '<d:propfind xmlns:d="DAV:">'
						. '<d:prop><d:href/></d:prop>'
					. '</d:propfind>',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 300 >= $response['response']['code'] ) {
			$error = new WP_Error( 'bad_request', 'Can not conenct to the backend' );
			$error->add_data( $response );
			return $error;
		}

		$xml = new SimpleXMLElement( $response['body'] );
		$xml->registerXPathNamespace( 'd', 'DAV:' );

		$parsed_url = wp_parse_url( $url );
		$basepath   = $parsed_url['path'] ?? '/';

		$files = array();
		foreach ( $xml->xpath( '//d:response' ) as $item ) {
			$href     = (string) $item->children( 'DAV:' )->href;
			$filepath = rawurldecode( str_replace( $basepath, '', $href ) );

			if ( '/' === $filepath ) {
				continue;
			}

			$pathinfo = pathinfo( $filepath );
			$is_file  = isset( $pathinfo['extension'] );

			if ( $is_file && 'csv' !== strtolower( $pathinfo['extension'] ) ) {
				continue;
			}

			if ( ! $is_file && 'files' === $endpoint ) {
				continue;
			} elseif ( $is_file && 'directories' === $endpoint ) {
				continue;
			}

			$files[] = array(
				'path'    => substr( $filepath, 1 ),
				'is_file' => $is_file,
			);
		}

		return array( 'data' => array( 'files' => $files ) );
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
		$response = $this->fetch( 'endpoints', $backend );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$endpoints = array();
		foreach ( $response['data']['files'] as $file ) {
			$endpoints[] = $file['path'];
		}

		return $endpoints;
	}

	/**
	 * Performs an introspection of the backend model and returns API fields
	 * and accepted content type.
	 *
	 * @param string      $filepath Filepath.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the model.
	 */
	public function get_endpoint_schema( $filepath, $backend, $method = null ) {
		if ( 'PUT' !== $method ) {
			return array();
		}

		$bridge = new Nextcloud_Form_Bridge(
			array(
				'name'     => '__nextcloud-' . time(),
				'endpoint' => $filepath,
				'backend'  => $backend,
			)
		);

		$headers = $bridge->table_headers();
		if ( is_wp_error( $headers ) || ! $headers ) {
			return array();
		}

		$fields = array();
		foreach ( $headers as $header ) {
			$fields[] = array(
				'name'   => $header,
				'schema' => array( 'type' => 'string' ),
			);
		}

		return $fields;
	}
}

Nextcloud_Addon::setup();

add_filter(
	'http_request_args',
	function ( $args ) {
		$args['timeout'] = 30;
		return $args;
	}
);
