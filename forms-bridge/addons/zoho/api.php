<?php
/**
 * Zoho API functions
 *
 * @package formsbridge
 */

use FORMS_BRIDGE\Zoho_Form_Bridge;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Creates a lead in Zoho picking from the payload all the knonw lead fields.
 *
 * @param array            $payload Bridge payload.
 * @param Zoho_Form_Bridge $bridge Bridge object.
 *
 * @return array|WP_Error New lead data or error response.
 */
function forms_bridge_zoho_crm_create_lead( $payload, $bridge ) {
	$lead = array(
		'Last_Name' => $payload['Last_Name'],
	);

	$lead_fields = array(
		'Owner',
		'Company',
		'First_Name',
		'Full_Name',
		'Email',
		'Phone',
		'Fax',
		'Mobile',
		'Website',
		'Lead_Source',
		'Lead_Status',
		'Industry',
		'No_of_Employees',
		'Annual_Revenue',
		'Street',
		'City',
		'Zip_Code',
		'State',
		'Country',
		'Description',
		'Tag',
	);

	foreach ( $lead_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$lead[ $field ] = $payload[ $field ];
		}
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'zoho-crm-create-lead',
				'scope'    => 'ZohoCRM.modules.leads.CREATE',
				'endpoint' => '/crm/v7/Leads/upsert',
				'template' => null,
			)
		)
		->submit( $lead );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = $response['data']['data'][0]['code'] ?? null;
	if ( 'DUPLICATE_DATA' === $code ) {
		return $response['data']['data'][0]['details']['duplicate_record'];
	} else {
		return $response['data']['data'][0]['details'];
	}
}

/**
 * Creates a lead in Zoho picking from the payload all the knonw lead fields.
 *
 * @param array            $payload Bridge payload.
 * @param Zoho_Form_Bridge $bridge Bridge object.
 *
 * @return array|WP_Error New lead data or error response.
 */
function forms_bridge_zoho_crm_create_contact( $payload, $bridge ) {
	$contact = array(
		'Last_Name' => $payload['Last_Name'],
	);

	$contact_fields = array(
		'Owner',
		'Lead_Source',
		'First_Name',
		'Full_Name',
		'Email',
		'Fax',
		'Mobile',
		'Phone',
		'Title',
		'Department',
		'Account_Name',
		'Mailing_Street',
		'Mailing_City',
		'Mailing_Zip',
		'Mailing_State',
		'Mailing_Country',
		'Description',
		'Tag',
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
		$account = forms_bridge_zoho_crm_create_account( $payload, $bridge );

		if ( is_wp_error( $account ) ) {
			return $account;
		}

		$payload['Account_Name'] = array( 'id' => $account['id'] );
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'zoho-crm-create-contact',
				'scope'    => 'ZohoCRM.modules.contacts.CREATE',
				'endpoint' => '/crm/v7/Contacts/upsert',
				'template' => null,
			)
		)
		->submit( $contact );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = $response['data']['data'][0]['code'];
	if ( 'DUPLICATE_DATA' === $code ) {
		return $response['data']['data'][0]['details']['duplicate_record'];
	} else {
		return $response['data']['data'][0]['details'];
	}
}

/**
 * Creates a lead in Zoho picking from the payload all the knonw lead fields.
 *
 * @param array            $payload Bridge payload.
 * @param Zoho_Form_Bridge $bridge Bridge object.
 *
 * @return array|WP_Error New lead data or error response.
 */
function forms_bridge_zoho_crm_create_account( $payload, $bridge ) {
	$company = array(
		'Account_Name' => $payload['Account_Name'],
	);

	$company_fields = array(
		'Billing_Street',
		'Billing_Code',
		'Billing_City',
		'Billing_State',
		'Billing_Country',
		'Phone',
		'Fax',
		'Mobile',
		'Website',
		'Owner',
		'Industry',
		'Ownership',
		'Employees',
		'Description',
		'Tag',
	);

	foreach ( $company_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$company[ $field ] = $payload[ $field ];
		}
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'zoho-crm-create-account',
				'scope'    => 'ZohoCRM.modules.accounts.CREATE',
				'endpoint' => '/crm/v7/Accounts/upsert',
				'template' => null,
			)
		)
		->submit( $company );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = $response['data']['data'][0]['code'] ?? null;
	if ( 'DUPLICATE_DATA' === $code ) {
		return $response['data']['data'][0]['details']['duplicate_record'];
	} else {
		return $response['data']['data'][0]['details'];
	}
}
