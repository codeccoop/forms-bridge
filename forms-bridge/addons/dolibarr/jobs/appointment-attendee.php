<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_dolibarr_appointment_attendee( $payload, $bridge ) {
	$contact = forms_bridge_dolibarr_create_contact( $payload, $bridge );

	if ( is_wp_error( $contact ) ) {
		return $contact;
	}

	$payload['socpeopleassigned'][ $contact['id'] ] = array(
		'id'            => $contact['id'],
		'mandatory'     => 0,
		'answer_status' => 0,
		'transparency'  => 0,
	);

	return $payload;
}

return array(
	'title'       => __( 'Appointment attendee', 'forms-bridge' ),
	'description' => __(
		'Create a contact and binds it to the appointment as an attendee',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_appointment_attendee',
	'input'       => array(
		array(
			'name'     => 'lastname',
			'required' => true,
			'schema'   => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'firstname',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'email',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'civility_code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'status',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'note_public',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'note_private',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'address',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'zip',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'town',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'country_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'state_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'region_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'phone_pro',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone_perso',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone_mobile',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'fax',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'url',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'socid',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'poste',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'stcomm_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'no_email',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'socpeopleassigned',
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					array(
						'type'                 => 'object',
						'properties'           => array(
							'id'            => array( 'type' => 'string' ),
							'mandatory'     => array( 'type' => 'string' ),
							'answer_status' => array( 'type' => 'string' ),
							'transparency'  => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
				),
			),
		),
	),
);
