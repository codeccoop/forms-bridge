<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_dolibarr_skip_contact( $payload, $bridge ) {
	$contact = forms_bridge_dolibarr_search_contact( $payload, $bridge );

	if ( is_wp_error( $contact ) ) {
		return $contact;
	}

	if ( isset( $contact['id'] ) ) {
		$patch       = $payload;
		$patch['id'] = $contact['id'];

		$response = forms_bridge_dolibarr_update_contact( $patch, $bridge );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return;
	}

	return $payload;
}

return array(
	'title'       => __( 'Skip if contact exists', 'forms-bridge' ),
	'description' => __(
		'Aborts form submission if the contact exists',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_skip_contact',
	'input'       => array(
		array(
			'name'     => 'email',
			'required' => true,
			'schema'   => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'firstname',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'lastname',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'socid',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'     => 'email',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'email' ),
		),
		array(
			'name'     => 'firstname',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'firstname' ),
		),
		array(
			'name'     => 'lastname',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'lastname' ),
		),
		array(
			'name'     => 'socid',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'socid' ),
		),
	),
);
