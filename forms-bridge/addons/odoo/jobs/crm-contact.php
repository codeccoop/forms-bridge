<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_odoo_crm_lead_contact( $payload, $bridge ) {
	$partner = forms_bridge_odoo_create_partner( $payload, $bridge );

	if ( is_wp_error( $partner ) ) {
		return $partner;
	}

	$payload['email_from'] = $partner['email'];

	if ( ! empty( $partner['parent_id'][0] ) ) {
		$payload['partner_id'] = $partner['parent_id'][0];
	} else {
		$payload['partner_id'] = $partner['id'];
	}

	return $payload;
}

return array(
	'title'       => __( 'CRM lead contact', 'forms-bridge' ),
	'description' => __(
		'Creates a new contact and sets its email as the email_from and its ID as the partner_id on the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_crm_lead_contact',
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
			'name'   => 'email_from',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'partner_id',
			'schema' => array( 'type' => 'integer' ),
		),
	),
);
