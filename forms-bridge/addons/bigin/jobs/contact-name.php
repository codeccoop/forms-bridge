<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_bigin_contact_name( $payload, $bridge ) {
	$contact = forms_bridge_bigin_create_contact( $payload, $bridge );

	if ( is_wp_error( $contact ) ) {
		return $contact;
	}

	$payload['Contact_Name'] = array(
		'id' => $contact['id'],
	);

	return $payload;
}

return array(
	'title'       => __( 'Contact name', 'forms-bridge' ),
	'description' => __(
		'Search for a contact by email or creates a new if it does\'t exists and replace the name by the ID on the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_bigin_contact_name',
	'input'       => array(
		array(
			'name'     => 'Email',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'     => 'First_Name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'     => 'Last_Name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'Phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Title',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Account_Name',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id' => array( 'type' => 'string' ),
				),
				'additionalProperties' => false,
			),
		),
		array(
			'name'   => 'Description',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'Contact_Name',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id' => array( 'type' => 'string' ),
				),
				'additionalProperties' => false,
			),
		),
		array(
			'name'   => 'Account_Name',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id' => array( 'type' => 'string' ),
				),
				'additionalProperties' => false,
			),
		),
	),
);
