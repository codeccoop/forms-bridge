<?php

namespace FORMS_BRIDGE;

use FBAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-gsheets-form-bridge.php';
require_once 'hooks.php';

/**
 * Google Sheets addon class.
 */
class Google_Sheets_Addon extends Addon {

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
	const BRIDGE = '\FORMS_BRIDGE\Google_Sheets_Form_Bridge';

	public function load() {
		parent::load();

		add_filter(
			'forms_bridge_prune_empties',
			static function ( $prune, $bridge ) {
				if ( $bridge->addon === 'gsheets' ) {
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
		$bridge = new Google_Sheets_Form_Bridge(
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
			return false;
		}

		$credential = $backend->credential;
		if ( ! $credential ) {
			return false;
		}

		$parsed = wp_parse_url( $backend->base_url );
		$host   = $parsed['host'] ?? '';

		if ( $host !== 'sheets.googleapis.com' ) {
			return false;
		}

		$access_token = $credential->get_access_token();
		return (bool) $access_token;
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
	 * Performs an introspection of the backend endpoint and returns API fields
	 * and accepted content type.
	 *
	 * @param string $endpoint Concatenation of spreadsheet ID and tab name.
	 * @param string $backend Backend name.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend ) {
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

Google_Sheets_Addon::setup();
