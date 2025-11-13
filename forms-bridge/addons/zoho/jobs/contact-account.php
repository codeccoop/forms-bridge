<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contact account', 'forms-bridge' ),
	'description' => __(
		'Create an account and sets its id as the Account_Name field on the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_zoho_crm_contact_account',
	'input'       => array(
		array(
			'name'     => 'Account_Name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'Rating',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'Billing_Street',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Billing_City',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Billing_Code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Billing_State',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Billing_Country',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Fax',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Mobile',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Website',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Owner',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id' => array( 'type' => 'integer' ),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
		),
		array(
			'name'   => 'Industry',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Ownership',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Employees',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'Description',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Tag',
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'properties'           => array(
						'name' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
					'required'             => array( 'name' ),
				),
			),
		),
	),
	'output'      => array(
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

function forms_bridge_zoho_crm_contact_account( $payload, $bridge ) {
	$account = forms_bridge_zoho_crm_create_account( $payload, $bridge );

	if ( is_wp_error( $account ) ) {
		return $account;
	}

	$payload['Account_Name'] = array( 'id' => $account['id'] );
	return $payload;
}
