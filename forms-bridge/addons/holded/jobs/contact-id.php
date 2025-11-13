<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_holded_contact_id( $payload, $bridge ) {
	$contact = forms_bridge_holded_create_contact( $payload, $bridge );

	if ( is_wp_error( $contact ) ) {
		return $contact;
	}

	$payload['contactId'] = $contact['id'];
	return $payload;
}

return array(
	'title'       => __( 'Contact ID', 'forms-bridge' ),
	'description' => __(
		'Creates a new contact and sets its ID as the contactId field of the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_holded_contact_id',
	'input'       => array(
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'CustomId',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'tradeName',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'email',
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
			'name'   => 'type',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'vatnumber',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'iban',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'swift',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'billAddress',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'address'     => array( 'type' => 'string' ),
					'postalCode'  => array( 'type' => 'string' ),
					'city'        => array( 'type' => 'string' ),
					'countryCode' => array( 'type' => 'string' ),
				),
				'additionalProperties' => true,
			),
		),
		array(
			'name'   => 'defaults',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'language' => array( 'type' => 'string' ),
				),
				'additionalProperties' => true,
			),
		),
		array(
			'name'   => 'tags',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'string' ),
				'additionalItems' => true,
			),
		),
		array(
			'name'   => 'note',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'isperson',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'contactPersons',
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'  => array( 'type' => 'string' ),
						'email' => array( 'type' => 'string' ),
						'phone' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
			),
		),
		array(
			'name'   => 'shippingAddresses',
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'        => array( 'type' => 'string' ),
						'address'     => array( 'type' => 'string' ),
						'city'        => array( 'type' => 'string' ),
						'postalCode'  => array( 'type' => 'string' ),
						'province'    => array( 'type' => 'string' ),
						'country'     => array( 'type' => 'string' ),
						'note'        => array( 'type' => 'string' ),
						'privateNote' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
			),
		),
	),
	'output'      => array(
		array(
			'name'   => 'contactId',
			'schema' => array( 'type' => 'string' ),
		),
	),
);
