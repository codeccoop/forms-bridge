<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_bigin_appointment_participant( $payload, $bridge ) {
	$contact = forms_bridge_bigin_create_contact( $payload, $bridge );

	if ( is_wp_error( $payload ) ) {
		return $payload;
	}

	$payload['Participants'][] = array(
		'type'        => 'contact',
		'participant' => $contact['id'],
	);

	return $payload;
}

return array(
	'title'       => __( 'Appointment participant', 'forms-bridge' ),
	'description' => __(
		'Search for a contact or creates a new one and sets its ID as appointment participant',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_bigin_appointment_participant',
	'input'       => array(
		array(
			'name'     => 'Last_Name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'First_Name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Full_Name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Email',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Mobile',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Mailing_Street',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Mailing_City',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Mailing_Zip',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Mailing_State',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Mailing_Country',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Description',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Account_Name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Title',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'Participants',
			'schema' => array(
				'type'            => 'array',
				'items'           => array(
					'type'       => 'object',
					'properties' => array(
						'type'        => array( 'type' => 'string' ),
						'participant' => array( 'type' => 'string' ),
					),
				),
				'additionalItems' => true,
			),
		),
	),
);
