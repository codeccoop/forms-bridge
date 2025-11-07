<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_holded_skip_contact( $payload, $bridge ) {
	$contact = forms_bridge_holded_search_contact( $payload, $bridge );

	if ( is_wp_error( $contact ) ) {
		return $contact;
	}

	if ( $contact ) {
		$payload['id'] = $contact['id'];
		$contact       = forms_bridge_holded_update_contact( $payload, $bridge );

		if ( is_wp_error( $contact ) ) {
			return $contact;
		}

		return;
	}

	return $payload;
}

return array(
	'title'       => __( 'Skip if contact exists', 'forms-bridge' ),
	'description' => __(
		'Search for a contact and skip submission if it exists',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_holded_skip_contact',
	'input'       => array(
		array(
			'name'   => 'phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'mobile',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'customId',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'     => 'phone',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'phone' ),
		),
		array(
			'name'     => 'mobile',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'mobile' ),
		),
		array(
			'name'     => 'customId',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'customId' ),
		),
	),
);
