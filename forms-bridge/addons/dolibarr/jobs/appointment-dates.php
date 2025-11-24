<?php
/**
 * Dolibarr appointment dates job
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_dolibarr_appointment_dates( $payload ) {
	$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $payload['date'] );
	if ( false === $datetime ) {
		return new WP_Error(
			'invalid-date',
			__( 'Invalid date time value', 'forms-bridge' )
		);
	}

	$timestamp           = $datetime->getTimestamp();
	$payload['datep']    = $timestamp;
	$payload['duration'] = floatval( $payload['duration'] ?? 1 );
	$payload['datef']    = intval( $payload['duration'] * 3600 + $timestamp );

	return $payload;
}

return array(
	'title'       => __( 'Appointment dates', 'forms-bridge' ),
	'description' => __(
		'Sets appointment start, end time and duration from datetime and duration fields.',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_appointment_dates',
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
			'name'   => 'datep',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'datef',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'duration',
			'schema' => array( 'type' => 'number' ),
		),
	),
);
