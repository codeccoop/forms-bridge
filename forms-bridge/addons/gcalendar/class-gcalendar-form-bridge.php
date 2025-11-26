<?php
/**
 * Class GCalendar_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implementation for the Google Calendar service.
 */
class GCalendar_Form_Bridge extends Form_Bridge {

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'gcalendar' );
	}

	/**
	 * Sends the payload to the backend to create a calendar event.
	 *
	 * @param array $payload Submission data.
	 * @param array $attachments Submission's attached files. Will be ignored.
	 *
	 * @return array|WP_Error Http request response.
	 */
	public function submit( $payload = array(), $attachments = array() ) {
		if ( ! $this->is_valid ) {
			return new WP_Error(
				'invalid_bridge',
				'Bridge data is invalid',
				(array) $this->data,
			);
		}

		$backend = $this->backend;
		if ( ! $backend ) {
			return new WP_Error( 'invalid_backend', 'Backend not found' );
		}

		$event = $this->transform_to_event( $payload );

		if ( is_wp_error( $event ) ) {
			return $event;
		}

		$endpoint = $this->endpoint;
		$method   = $this->method;

		return $this->backend->$method( $endpoint, $event );
	}

	/**
	 * Transforms the form payload into a Google Calendar event structure.
	 *
	 * @param array $payload Form submission payload.
	 *
	 * @return array|WP_Error Calendar event data.
	 */
	private function transform_to_event( $payload ) {
		$event = array();

		if ( isset( $payload['summary'] ) ) {
			$event['summary'] = $payload['summary'];
		}

		if ( isset( $payload['description'] ) ) {
			$event['description'] = $payload['description'];
		}

		if ( isset( $payload['location'] ) ) {
			$event['location'] = $payload['location'];
		}

		if ( isset( $payload['start'] ) ) {
			$event['start'] = $this->parse_datetime( $payload['start'] );
		}

		if ( isset( $payload['end'] ) ) {
			$event['end'] = $this->parse_datetime( $payload['end'] );
		}

		if ( ! isset( $event['end'] ) && isset( $event['start']['dateTime'] ) ) {
			$start_time   = strtotime( $event['start']['dateTime'] );
			$end_time     = $start_time + 3600;
			$event['end'] = array(
				'dateTime' => gmdate( 'Y-m-d\TH:i:s', $end_time ),
				'timeZone' => $event['start']['timeZone'],
			);
		}

		if ( isset( $payload['attendees'] ) ) {
			$attendees = array();
			if ( is_string( $payload['attendees'] ) ) {
				$emails = array_map( 'trim', explode( ',', $payload['attendees'] ) );
				foreach ( $emails as $email ) {
					if ( is_email( $email ) ) {
						$attendees[] = array( 'email' => $email );
					}
				}
			} elseif ( is_array( $payload['attendees'] ) ) {
				foreach ( $payload['attendees'] as $attendee ) {
					if ( is_string( $attendee ) && is_email( $attendee ) ) {
						$attendees[] = array( 'email' => $attendee );
					} elseif ( is_array( $attendee ) && isset( $attendee['email'] ) ) {
						$attendees[] = $attendee;
					}
				}
			}

			if ( ! empty( $attendees ) ) {
				$event['attendees'] = $attendees;
			}
		}

		if ( isset( $payload['reminders'] ) ) {
			$event['reminders'] = $payload['reminders'];
		}

		if ( isset( $payload['colorId'] ) ) {
			$event['colorId'] = $payload['colorId'];
		}

		if ( isset( $payload['sendUpdates'] ) ) {
			$event['sendUpdates'] = (bool) $payload['sendUpdates'];
		}

		if ( ! ( isset( $event['start'] ) && isset( $event['end'] ) ) ) {
			return new WP_Error(
				'missing_event_dates',
				'Event must have a start and an end date',
				$payload,
			);
		}

		if ( ! isset( $event['summary'] ) ) {
			return new WP_Error(
				'missing_summary',
				'Event must have a summary (title)',
				$payload
			);
		}

		return $event;
	}

	/**
	 * Parses a datetime value into Google Calendar format.
	 *
	 * @param mixed $datetime DateTime value (timestamp, string, or array).
	 *
	 * @return array DateTime structure for Google Calendar.
	 */
	private function parse_datetime( $datetime ) {
		if ( is_array( $datetime ) && isset( $datetime['dateTime'] ) ) {
			return $datetime;
		}

		$timezone = wp_timezone_string();

		if ( is_numeric( $datetime ) ) {
			$dt = gmdate( 'Y-m-d\TH:i:s', $datetime );
		} elseif ( is_string( $datetime ) ) {
			$timestamp = strtotime( $datetime );
			if ( false === $timestamp ) {
				$dt = gmdate( 'Y-m-d\TH:i:s' );
			} else {
				$dt = gmdate( 'Y-m-d\TH:i:s', $timestamp );
			}
		} else {
			$dt = gmdate( 'Y-m-d\TH:i:s' );
		}

		return array(
			'dateTime' => $dt,
			'timeZone' => $timezone,
		);
	}
}
