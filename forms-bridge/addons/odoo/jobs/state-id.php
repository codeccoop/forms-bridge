<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_odoo_state_id_from_code( $payload, $bridge ) {
	global $forms_bridge_iso2_countries;

	if ( ! isset( $forms_bridge_iso2_countries[ $payload['country_code'] ] ) ) {
		return new WP_Error( 'Invalid ISO-2 country code', 'forms-bridge' );
	}

	$states = "forms_bridge_odoo_{$payload['country_code']}_states";
	global $$states;
	if ( ! isset( $$states ) ) {
		return new WP_Error( 'Unkown country states', 'forms-bridge' );
	}

	if ( ! isset( $$states[ $payload['state_code'] ] ) ) {
		return new WP_Error( 'Invalid state code', 'forms-bridge' );
	}

	$response = $bridge
		->patch(
			array(
				'endpoint' => 'res.country.state',
				'method'   => 'search',
			)
		)
		->submit( array( array( 'code', '=', $payload['state_code'] ) ) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$payload['state_id'] = $response['data']['result'][0];
	return $payload;
}

return array(
	'title'       => __( 'State ID from code', 'forms-bridge' ),
	'description' => __(
		'Given a iso2 country code code and a state odoo code gets the internal state ID',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_state_id_from_code',
	'input'       => array(
		array(
			'name'     => 'country_code',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'     => 'state_code',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
	),
	'output'      => array(
		array(
			'name'   => 'country_code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'state_code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'state_id',
			'schema' => array( 'type' => 'integer' ),
		),
	),
);
