<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Candidate', 'forms-bridge' ),
	'description' => __(
		'Creates a recruitement candidate and sets its ID as the candidate_id field of the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_create_candidate',
	'input'       => array(
		array(
			'name'     => 'partner_name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'partner_phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'email_from',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'user_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'type_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'partner_id',
			'schema' => array( 'type' => 'integer' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'candidate_id',
			'schema' => array( 'type' => 'integer' ),
		),
	),
);

function forms_bridge_odoo_create_candidate( $payload, $bridge ) {
	$candidate = array(
		'partner_name' => $payload['partner_name'],
	);

	$fields = array(
		'partner_id',
		'partner_phone',
		'email_from',
		'user_id',
		'type_id',
	);

	foreach ( $fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$candidate[ $field ] = $payload[ $field ];
		}
	}

	$query = array( array( 'partner_name', '=', $candidate['partner_name'] ) );

	if ( isset( $candidate['email_from'] ) ) {
		$query[] = array( 'email_from', '=', $candidate['email_from'] );
	}

	$response = $bridge
		->patch(
			array(
				'name'     => '__odoo-search-candidate',
				'endpoint' => 'hr.candidate',
				'method'   => 'search',
			)
		)
		->submit( $query );

	if ( ! is_wp_error( $response ) ) {
		$payload['candidate_id'] = (int) $response['data']['result'][0];
		return $payload;
	}

	$response = $bridge
		->patch(
			array(
				'name'     => '__odoo-create-candidate',
				'endpoint' => 'hr.candidate',
			)
		)
		->submit( $candidate );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$payload['candidate_id'] = (int) $response['data']['result'];

	return $payload;
}
