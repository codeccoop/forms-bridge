<?php
/**
 * Class GSheets_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FBAPI;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-gsheets-form-bridge.php';
require_once 'hooks.php';

/**
 * Google Sheets addon class.
 */
class GSheets_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'Google Sheets';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'gsheets';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\GSheets_Form_Bridge';

	/**
	 * Addon loader. Set up hooks to skip payload prunes if it comes from a
	 * google sheets bridge.
	 */
	public function load() {
		parent::load();

		add_filter(
			'forms_bridge_prune_empties',
			static function ( $prune, $bridge ) {
				if ( 'gsheets' === $bridge->addon ) {
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
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => '__gsheets-' . time(),
				'backend'  => $backend,
				'endpoint' => '/',
				'method'   => 'GET',
				'tab'      => 'foo',
			)
		);

		$backend = $bridge->backend;
		if ( ! $backend ) {
			Logger::log( 'Google Sheets backend ping error: Bridge has no valid backend', Logger::ERROR );
			return false;
		}

		$credential = $backend->credential;
		if ( ! $credential ) {
			Logger::log( 'Google Sheets backend ping error: Backend has no valid credential', Logger::ERROR );
			return false;
		}

		$parsed = wp_parse_url( $backend->base_url );
		$host   = $parsed['host'] ?? '';

		if ( 'sheets.googleapis.com' !== $host ) {
			Logger::log( 'Google Sheets backend ping error: Backend does not point to the Google Sheets API endpoints', Logger::ERROR );
			return false;
		}

		$access_token = $credential->get_access_token();

		if ( ! $access_token ) {
			Logger::log( 'Google Sheets backend ping error: Unable to recover the credential access token', Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend endpoint and retrive the response data.
	 *
	 * @param string $endpoint Concatenation of spreadsheet ID and tab name.
	 * @param string $backend Backend name.
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $endpoint, $backend ) {
		$backend = FBAPI::get_backend( $backend );
		if ( ! $backend ) {
			return new WP_Error( 'invalid_backend' );
		}

		$credential = $backend->credential;
		if ( ! $credential ) {
			return new WP_Error( 'invalid_credential' );
		}

		$access_token = $credential->get_access_token();
		if ( ! $access_token ) {
			return new WP_Error( 'invalid_credential' );
		}

		$response = http_bridge_get(
			'https://www.googleapis.com/drive/v3/files',
			array( 'q' => "mimeType = 'application/vnd.google-apps.spreadsheet'" ),
			array(
				'Authorization' => "Bearer {$access_token}",
				'Accept'        => 'application/json',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $response;
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

		if ( is_wp_error( $response ) || empty( $response['data']['files'] ) ) {
			return array();
		}

		return array_map(
			function ( $file ) {
				return '/v4/spreadsheets/' . $file['id'];
			},
			$response['data']['files']
		);
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields
	 * and accepted content type.
	 *
	 * @param string      $endpoint Concatenation of spreadsheet ID and tab name.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		if ( 'POST' !== $method ) {
			return array();
		}

		$bridge  = null;
		$bridges = FBAPI::get_addon_bridges( self::NAME );
		foreach ( $bridges as $candidate ) {
			$data = $candidate->data();
			if ( ! $data ) {
				continue;
			}

			if (
				$data['endpoint'] === $endpoint &&
				$data['backend'] === $backend
			) {
				/**
				 * Current bridge.
				 *
				 * @var GSheets_Form_Bridge
				 */
				$bridge = $candidate;
			}
		}

		if ( ! isset( $bridge ) ) {
			return array();
		}

		$headers = $bridge->get_headers();

		if ( is_wp_error( $headers ) ) {
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

GSheets_Addon::setup();
