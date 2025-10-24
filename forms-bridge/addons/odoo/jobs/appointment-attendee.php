<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_odoo_appointment_attendees( $payload, $bridge ) {
	$partner = forms_bridge_odoo_create_partner( $payload, $bridge );

	if ( is_wp_error( $partner ) ) {
		return $partner;
	}

	$payload['partner_ids'][] = $partner['id'];

	if ( isset( $payload['user_id'] ) ) {
		$user_response = $bridge
			->patch(
				array(
					'name'     => 'odoo-get-user-by-id',
					'endpoint' => 'res.users',
					'method'   => 'read',
				)
			)
			->submit( array( $payload['user_id'] ) );

		if ( is_wp_error( $user_response ) ) {
			return $user_response;
		}

		$payload['partner_ids'][] =
			$user_response['data']['result'][0]['partner_id'][0];
	}

	return $payload;
}

return array(
	'title'       => __( 'Appointment attendees', 'forms-bridge' ),
	'description' => __(
		'Search for partner by email or creates a new one and sets it as the appointment attendee. If user_id, also adds user as attendee.',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_appointment_attendees',
	'input'       => array(
		array(
			'name'     => 'email',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'title',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'lang',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'vat',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'employee',
			'schema' => array( 'type' => 'boolean' ),
		),
		array(
			'name'   => 'function',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'mobile',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'website',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'street',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'street2',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'zip',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'city',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'country_code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'parent_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'additional_info',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'is_public',
			'schema' => array( 'type' => 'boolean' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'user_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'partner_ids',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'integer' ),
				'additionalItems' => true,
			),
		),
	),
);
