<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

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
