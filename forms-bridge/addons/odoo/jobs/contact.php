<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_odoo_contact_id( $payload, $bridge ) {
	$contact = forms_bridge_odoo_create_partner( $payload, $bridge );

	if ( is_wp_error( $contact ) ) {
		return $contact;
	}

	$payload['partner_id'] = $contact['id'];
	return $payload;
}

return array(
	'title'       => __( 'Contact', 'forms-bridge' ),
	'description' => __(
		'Creates a contact and sets its ID as the partner_id field of the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_contact_id',
	'input'       => array(
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
			'name'   => 'email',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'mobile',
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
			'name'   => 'city',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'zip',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'country_code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'country_id',
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
		array(
			'name'   => 'parent_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'function',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'partner_id',
			'schema' => array( 'type' => 'integer' ),
		),
	),
);
