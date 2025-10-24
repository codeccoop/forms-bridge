<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_brevo_create_company( $payload, $bridge ) {
	$company = array(
		'name' => $payload['name'],
	);

	$company_fields = array(
		'attributes',
		'countryCode',
		'linkedContactsIds',
		'linkedDealsIds',
	);

	foreach ( $company_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$company[ $field ] = $payload[ $field ];
		}
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'brevo-create-company',
				'endpoint' => '/v3/companies',
				'method'   => 'POST',
			)
		)
		->submit( $company );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	return $response['data'];
}

function forms_bridge_brevo_create_contact( $payload, $bridge ) {
	$contact = array(
		'email' => $payload['email'],
	);

	$contact_fields = array(
		'ext_id',
		'attributes',
		'emailBlacklisted',
		'smsBlacklisted',
		'listIds',
		'updateEnabled',
		'smtpBlacklistSender',
	);

	foreach ( $contact_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$contact[ $field ] = $payload[ $field ];
		}
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'brevo-create-contact',
				'endpoint' => '/v3/contacts',
				'method'   => 'POST',
			)
		)
		->submit( $contact );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	return $response['data'];
}
