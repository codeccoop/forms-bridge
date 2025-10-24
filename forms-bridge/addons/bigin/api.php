<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_bigin_create_contact( $payload, $bridge ) {
	$contact = array(
		'Last_Name' => $payload['Last_Name'],
	);

	$contact_fields = array(
		'Owner',
		'Full_Name',
		'First_Name',
		'Email',
		'Phone',
		'Mobile',
		'Title',
		'Account_Name',
		'Description',
		'Mailing_Street',
		'Mailing_City',
		'Mailing_Zip',
		'Mailing_State',
		'Mailing_Country',
	);

	foreach ( $contact_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$contact[ $field ] = $payload[ $field ];
		}
	}

	if (
		isset( $payload['Account_Name'] ) &&
		is_string( $payload['Account_Name'] )
	) {
		$account = forms_bridge_bigin_create_account( $payload, $bridge );

		if ( is_wp_error( $account ) ) {
			return $account;
		}

		$payload['Account_Name'] = array( 'id' => $account['id'] );
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'zoho-bigin-create-contact',
				'scope'    => 'ZohoBigin.modules.contacts.CREATE',
				'endpoint' => '/bigin/v2/Contacts/upsert',
				'template' => null,
			)
		)
		->submit( $contact );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = $response['data']['data'][0]['code'] ?? null;
	if ( $code === 'DUPLICATE_DATA' ) {
		return $response['data']['data'][0]['details']['duplicate_record'];
	} else {
		return $response['data']['data'][0]['details'];
	}
}

function forms_bridge_bigin_create_account( $payload, $bridge ) {
	$company = array(
		'Account_Name' => $payload['Account_Name'],
	);

	$company_fields = array(
		'Owner',
		'Phone',
		'Website',
		'Billing_Street',
		'Billing_Code',
		'Billing_City',
		'Billing_State',
		'Billing_Country',
		'Description',
	);

	foreach ( $company_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$company[ $field ] = $payload[ $field ];
		}
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'zoho-bigin-create-account',
				'scope'    => 'ZohoBigin.modules.accounts.CREATE',
				'endpoint' => '/bigin/v2/Accounts/upsert',
			)
		)
		->submit( $company );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = $response['data']['data'][0]['code'] ?? null;
	if ( $code === 'DUPLICATE_DATA' ) {
		return $response['data']['data'][0]['details']['duplicate_record'];
	} else {
		return $response['data']['data'][0]['details'];
	}
}
