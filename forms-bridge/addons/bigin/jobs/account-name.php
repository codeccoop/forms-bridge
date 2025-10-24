<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_zoho_bigin_account_name( $payload, $bridge ) {
	$account = forms_bridge_bigin_create_account( $payload, $bridge );

	if ( is_wp_error( $account ) ) {
		return $account;
	}

	$payload['Account_Name'] = array(
		'id' => $account['id'],
	);

	return $payload;
}

return array(
	'title'       => __( 'Account name', 'forms-bridge' ),
	'description' => __(
		'Search for an account by name or creates a new if it does\'t exists and replace the name by the ID on the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_zoho_bigin_account_name',
	'input'       => array(
		array(
			'name'     => 'Account_Name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'Owner',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id' => array( 'type' => 'string' ),
				),
				'additionalProperties' => false,
				'required'             => array( 'id' ),
			),
		),
		array(
			'name'   => 'Phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Website',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Billing_Street',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Billing_Code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Billing_City',
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
			'name'   => 'Description',
			'schema' => array( 'type' => 'string' ),
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
