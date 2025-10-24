<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Linked company', 'forms-bridge' ),
	'description' => __(
		'Creates a new company and inserts its ID in the linkedCompaniesIds array field of the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_brevo_linked_company',
	'input'       => array(
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
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
			'name'   => 'countryCode',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'linkedContactsIds',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'integer' ),
				'additionalItems' => true,
			),
		),
	),
	'output'      => array(
		array(
			'name'   => 'linkedCompaniesIds',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'string' ),
				'additionalItems' => true,
			),
		),
	),
);

function forms_bridge_brevo_linked_company( $payload, $bridge ) {
	$company = forms_bridge_brevo_create_company( $payload, $bridge );

	if ( is_wp_error( $company ) ) {
		return $company;
	}

	$payload['linkedCompaniesIds'][] = $company['id'];

	return $payload;
}
