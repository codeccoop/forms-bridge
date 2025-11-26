<?php
/**
 * Google Calendar event dates job
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Event dates', 'forms-bridge' ),
	'description' => array(
		'Given a datetime and a duration in hours and minuts, sets up the event dates',
		'forms-bridge',
	),
	'method'      => 'forms_bridge_gcalendar_event_dates',
	'input'       => array(
		array(
			'name'     => 'datetime',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'     => 'duration_hours',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'duration_minutes',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'start',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'dateTime' => array( 'type' => 'string' ),
					'timeZone' => array( 'type' => 'string' ),
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
					'dateTime' => array( 'type' => 'string' ),
					'timeZone' => array( 'type' => 'string' ),
				),
				'additionalProperties' => false,
				'required'             => array( 'dateTime' ),
			),
		),
	),
);

/**
 * Given a datetime and a duration in hours and minuts, sets up the event dates,
 *
 * @param array $payload Bridge payload.
 *
 * @return array
 */
function forms_bridge_gcalendar_event_dates( $payload ) {
	$datetime = $payload['datetime'];
	$time     = strtotime( $datetime );
	$endtime  = $time + $payload['duration_hours'] * 3600 + ( $payload['duration_minutes'] ?? 0 ) * 60;

	$timezone = wp_timezone_string();

	$payload['start'] = array(
		'dateTime' => gmdate( 'Y-m-d\TH:i:s', $time ),
		'timeZone' => $timezone,
	);

	$payload['end'] = array(
		'dateTime' => gmdate( 'Y-m-d\TH:i:s', $endtime ),
		'timeZone' => $timezone,
	);

	return $payload;
}
