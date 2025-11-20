<?php
/**
 * Brevo API functions
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Creates a company in Brevo picking from the payload all known company fields.
 *
 * @param array             $payload Bridge payload.
 * @param Brevo_Form_Bridge $bridge Bridge object.
 *
 * @return array|WP_Error New company data or response error.
 */
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

/**
 * Creates a contact in Brevo picking from the payload all known contact fields.
 *
 * @param array             $payload Bridge payload.
 * @param Brevo_Form_Bridge $bridge Bridge object.
 *
 * @return array|WP_Error New contact data or response error.
 */
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
