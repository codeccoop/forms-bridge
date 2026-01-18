<?php
/**
 * Date fields to date job
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'name'        => 'date-fields-to-date',
	'title'       => __( 'Format date fields', 'forms-bridge' ),
	'description' => __(
		'Gets date, hour and minute fields and merge its values into a date with format Y-m-d H:M:S',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_job_format_date_fields',
	'input'       => array(
		array(
			'name'     => 'date',
			'required' => true,
			'schema'   => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'hour',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'minute',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'datetime',
			'schema' => array( 'type' => 'string' ),
		),
	),
);

/**
 * Date fields to date job method.
 *
 * @param array $payload Bridge payload.
 *
 * @return array|WP_Error
 */
function forms_bridge_job_format_date_fields( $payload ) {
	$date   = $payload['date'];
	$hour   = $payload['hour'] ?? '00';
	$minute = $payload['minute'] ?? '00';

	$form_data  = FBAPI::get_current_form();
	$date_index = array_search(
		'date',
		array_column( $form_data['fields'], 'type' ),
		true
	);

	if ( false !== $date_index ) {
		$date_format = $form_data['fields'][ $date_index ]['format'];
	} else {
		$date_format = 'yyyy-mm-dd';
	}

	if ( strstr( $date_format, '-' ) ) {
		$separator = '-';
	} elseif ( strstr( $date_format, '.' ) ) {
		$separator = '.';
	} elseif ( strstr( $date_format, '/' ) ) {
		$separator = '/';
	}

	$year  = null;
	$month = null;
	$day   = null;

	switch ( substr( $date_format, 0, 1 ) ) {
		case 'y':
			$chunks = explode( $separator, $date );

			if ( 3 === count( $chunks ) ) {
				[$year, $month, $day] = $chunks;
			}

			break;
		case 'm':
			$chunks = explode( $separator, $date );

			if ( 3 === count( $chunks ) ) {
				[$month, $day, $year] = $chunks;
			}

			break;
		case 'd':
			$chunks = explode( $separator, $date );

			if ( 3 === count( $chunks ) ) {
				[$day, $month, $year] = $chunks;
			}

			break;
	}

	if ( ! $year || ! $month || ! $day ) {
		return new WP_Error(
			'invalid-date',
			__( 'Invalid date format', 'forms-bridge' )
		);
	}

	$date = "{$year}-{$month}-{$day}";

	if ( preg_match( '/(am|pm)/i', $hour, $matches ) ) {
		$hour = (int) $hour;
		if ( strtolower( $matches[0] ) === 'pm' ) {
			$hour += 12;
		}
	}

	$time = strtotime( "{$date} {$hour}:{$minute}" );

	if ( false === $time ) {
		return new WP_Error(
			'invalid-date',
			__( 'Invalid date format', 'forms-bridge' )
		);
	}

	$payload['datetime'] = date( 'Y-m-d H:i:s', $time );
	return $payload;
}
