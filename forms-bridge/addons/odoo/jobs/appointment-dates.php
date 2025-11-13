<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_odoo_appointment_dates( $payload ) {
	$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $payload['date'] );
	if ( $datetime === false ) {
		return new WP_Error(
			'invalid-date',
			__( 'Invalid date time value', 'forms-bridge' )
		);
	}

	$timestamp = $datetime->getTimestamp();

	$duration = floatval( $payload['duration'] ?? 1 );

	$payload['start'] = date( 'Y-m-d H:i:s', $timestamp );

	$end             = $duration * 3600 + $timestamp;
	$payload['stop'] = date( 'Y-m-d H:i:s', $end );

	return $payload;
}

return array(
	'title'       => __( 'Appointment dates', 'forms-bridge' ),
	'description' => __(
		'Sets appointment start and stop time from "timestamp" and "duration" fields.',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_appointment_dates',
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
			'name'   => 'start',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'stop',
			'schema' => array( 'type' => 'string' ),
		),
	),
);
