<?php
/**
 * Appointment dates Holded add-on job.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Given a date string in format 'Y-m-d H:i:s' and a duration as a numeric value, it creates
 * two payload fields:
 *   - startDate: The date value in timestamp format.
 *   - duration: As a float value with 1 as its fallback value.
 *
 * @param array $payload Bridge payload.
 *
 * @return array|WP_Error
 */
function forms_bridge_holded_appointment_dates( $payload ) {
	$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $payload['date'] );
	if ( $datetime === false ) {
		return new WP_Error(
			'invalid-date',
			__( 'Invalid date time value', 'forms-bridge' )
		);
	}

	$timestamp            = $datetime->getTimestamp();
	$payload['startDate'] = $timestamp;
	$payload['duration']  = floatval( $payload['duration'] ?? 1 );

	return $payload;
}

return array(
	'title'       => __( 'Appointment dates', 'forms-bridge' ),
	'description' => __(
		'Sets appointment start time and duration from datetime and duration fields',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_holded_appointment_dates',
	'input'       => array(
		array(
			'name'     => 'date',
			'required' => true,
			'schema'   => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'duration',
			'schema' => array( 'type' => 'number' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'startDate',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'duration',
			'schema' => array( 'type' => 'number' ),
		),
	),
);
