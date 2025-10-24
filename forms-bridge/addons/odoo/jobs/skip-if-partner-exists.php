<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_odoo_skip_if_partner_exists( $payload, $bridge ) {
	$query = array( array( 'name', '=', $payload['name'] ) );

	if ( isset( $payload['email'] ) ) {
		$query[] = array( 'email', '=', $payload['email'] );
	}

	if ( isset( $payload['vat'] ) ) {
		$query[] = array( 'vat', '=', $payload['vat'] );
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'odoo-search-contact-by-email',
				'method'   => 'search',
				'endpoint' => 'res.partner',
			)
		)
		->submit( $query );

	if ( ! is_wp_error( $response ) ) {
		$partner_id = $response['data']['result'][0];

		$response = $bridge
			->patch(
				array(
					'name'     => 'odoo-update-contact',
					'method'   => 'write',
					'endpoint' => 'res.partner',
				)
			)
			->submit( array( $partner_id ), $payload );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return;
	}

	return $payload;
}

return array(
	'title'       => __( 'Skip if contact exists', 'forms-bridge' ),
	'description' => __(
		'Search contacts by name, email and vat and skip submission if it exists',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_skip_if_partner_exists',
	'input'       => array(
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'email',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'vat',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'name' ),
		),
		array(
			'name'     => 'email',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'email' ),
		),
		array(
			'name'     => 'vat',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'vat' ),
		),
	),
);
