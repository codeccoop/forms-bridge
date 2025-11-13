<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_odoo_contact_company( $payload, $bridge ) {
	$company = forms_bridge_odoo_create_company( $payload, $bridge );

	if ( is_wp_error( $company ) ) {
		return $company;
	}

	$payload['parent_id'] = $company['id'];
	return $payload;
}

return array(
	'title'       => __( 'Contact\'s company', 'forms-bridge' ),
	'description' => __(
		'Creates a company and sets its ID as the parent_id of the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_contact_company',
	'input'       => array(
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
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
	),
	'output'      => array(
		array(
			'name'   => 'parent_id',
			'schema' => array( 'type' => 'integer' ),
		),
	),
);
