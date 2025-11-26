<?php
/**
 * Class GCalendar_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FBAPI;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-gcalendar-form-bridge.php';
require_once 'hooks.php';

/**
 * Google Calendar addon class.
 */
class GCalendar_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'Google Calendar';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'gcalendar';

	/**
	 * Handles the addon's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\GCalendar_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => '__gcalendar-' . time(),
				'backend'  => $backend,
				'endpoint' => '/calendar/v3/users/me/calendarList',
				'method'   => 'GET',
			)
		);

		$backend = $bridge->backend;
		if ( ! $backend ) {
			Logger::log( 'Google Calendar backend ping error: Bridge has no valid backend', Logger::ERROR );
			return false;
		}

		$credential = $backend->credential;
		if ( ! $credential ) {
			Logger::log( 'Google Calendar backend ping error: Backend has no valid credential', Logger::ERROR );
			return false;
		}

		$parsed = wp_parse_url( $backend->base_url );
		$host   = $parsed['host'] ?? '';

		if ( 'www.googleapis.com' !== $host ) {
			Logger::log( 'Google Calendar backend ping error: Backend does not point to the Google Calendar API endpoints', Logger::ERROR );
			return false;
		}

		$access_token = $credential->get_access_token();

		if ( ! $access_token ) {
			Logger::log( 'Google Calendar backend ping error: Unable to recover the credential access token', Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend endpoint and retrieve the response data.
	 *
	 * @param string $endpoint Calendar ID or endpoint.
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
			'https://www.googleapis.com/calendar/v3/users/me/calendarList',
			array(),
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
	 * @param string      $endpoint Calendar ID.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		if ( ! in_array( $method, array( 'POST', 'PUT' ), true ) ) {
			return array();
		}

		return array(
			array(
				'name'   => 'summary',
				'schema' => array(
					'type'        => 'string',
					'description' => 'Event title',
				),
			),
			array(
				'name'   => 'description',
				'schema' => array(
					'type'        => 'string',
					'description' => 'Event description',
				),
			),
			array(
				'name'   => 'location',
				'schema' => array(
					'type'        => 'string',
					'description' => 'Event location',
				),
			),
			array(
				'name'   => 'start',
				'schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'dateTime' => array(
							'type'        => 'string',
							'description' => 'Start date and time (ISO 8601 format)',
						),
						'timeZone' => array(
							'type'        => 'string',
							'description' => 'Start timezone',
						),
					),
					'additionalProperties' => false,
					'required'             => array( 'dateTime' ),
				),
			),
			array(
				'name'   => 'end',
				'schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'dateTime' => array(
							'type'        => 'string',
							'description' => 'End date and time (ISO 8601 format)',
						),
						'timeZone' => array(
							'type'        => 'string',
							'description' => 'End timezone',
						),
					),
					'additionalProperties' => false,
					'required'             => array( 'dateTime' ),
				),
			),
			array(
				'name'   => 'attendees',
				'schema' => array(
					'type'            => 'array',
					'items'           => array(
						'type'        => 'string',
						'description' => 'attendee email address',
					),
					'additionalItems' => true,
				),
			),
		);
	}
}

GCalendar_Addon::setup();
