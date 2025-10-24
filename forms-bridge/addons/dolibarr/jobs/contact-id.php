<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_dolibarr_contact_ids( $payload, $bridge ) {
	$contact = forms_bridge_dolibarr_create_contact( $payload, $bridge );

	if ( is_wp_error( $contact ) ) {
		return $contact;
	}

	$payload['contact_ids'][] = (int) $contact['id'];
	return $payload;
}

return array(
	'title'       => __( 'Contact', 'forms-bridge' ),
	'description' => __(
		'Creates a contact and adds its ID to the contact_ids field of the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_contact_ids',
	'input'       => array(
		array(
			'name'     => 'lastname',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
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
			'name'   => 'socid',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'poste',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'status',
			'schema' => array( 'type' => 'string' ),
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
			'name'   => 'state_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'region_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'url',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'no_email',
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
			'name'   => 'stcomm_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'default_lang',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'contact_ids',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'integer' ),
				'additionalItems' => true,
			),
		),
	),
);
