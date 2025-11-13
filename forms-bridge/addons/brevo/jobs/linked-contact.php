<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Linked contact', 'forms-bridge' ),
	'description' => __(
		'Creates a new contact and inserts its ID in the linkedContactsIds array field of the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_brevo_linked_contact',
	'input'       => array(
		array(
			'name'     => 'email',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'ext_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'attributes',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => true,
			),
		),
		array(
			'name'   => 'emailBlacklisted',
			'schema' => array( 'type' => 'boolean' ),
		),
		array(
			'name'   => 'smsBlacklisted',
			'schema' => array( 'type' => 'boolean' ),
		),
		array(
			'name'   => 'listIds',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'integer' ),
				'additionalItems' => true,
			),
		),
		array(
			'name'   => 'updateEnabled',
			'schema' => array( 'type' => 'boolean' ),
		),
		array(
			'name'   => 'smtpBlacklistSender',
			'schema' => array( 'type' => 'boolean' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'linkedContactsIds',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'integer' ),
				'additionalItems' => true,
			),
		),
	),
);

function forms_bridge_brevo_linked_contact( $payload, $bridge ) {
	$contact = forms_bridge_brevo_create_contact( $payload, $bridge );

	if ( is_wp_error( $contact ) ) {
		return $contact;
	}

	$payload['linkedContactsIds'][] = $contact['id'];

	return $payload;
}
