<?php
/**
 * SuiteCRM contact job
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contact', 'forms-bridge' ),
	'description' => __( 'Creates a contact in SuiteCRM', 'forms-bridge' ),
	'method'      => 'forms_bridge_suitecrm_create_contact',
	'input'       => array(
		array(
			'name'     => 'last_name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'first_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'assigned_user_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'assigned_user_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'salutation',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'full_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'birthdate',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'title',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'photo',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'department',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'do_not_call',
			'schema' => array( 'type' => 'boolean' ),
		),
		array(
			'name'   => 'phone_home',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone_work',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone_mobile',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone_other',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone_fax',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'email',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'email1',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'email2',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'email_address_non_primary',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'email_and_name1',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'assistant',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'assistant_phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'primary_address_street',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'primary_address_street2',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'primary_address_street3',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'primary_address_city',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'primary_address_postalcode',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'primary_address_state',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'primary_address_country',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'lawful_basis_source',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'lead_source',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'account_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'account_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'opportunity_role',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'opportunity_role_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'campaign_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'campaign_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'accept_status_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'accept_status_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'event_status_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'event_status_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'event_invite_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'event_accept_status',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'contact_id',
			'schema' => array( 'type' => 'string' ),
		),
	),
);

/**
 * Creates a new contact and add its ID to the payload.
 *
 * @param array       $payload Bridge payload.
 * @param Form_Bridge $bridge Bridge object.
 *
 * @return array
 */
function forms_bridge_suitecrm_create_contact( $payload, $bridge ) {
	$contact = array(
		'last_name' => $payload['last_name'],
	);

	$contact_fields = array(
		'first_name',
		'assigned_user_id',
		'assigned_user_name',
		'salutation',
		'full_name',
		'birthdate',
		'title',
		'photo',
		'department',
		'do_not_call',
		'phone_home',
		'phone_work',
		'phone_mobile',
		'phone_fax',
		'phone_other',
		'email1',
		'email',
		'email2',
		'email_address_non_primary',
		'email_and_name1',
		'assistant',
		'assistant_phone',
		'primary_address_street',
		'primary_address_street2',
		'primary_address_street3',
		'primary_address_postalcode',
		'primary_address_city',
		'primary_address_state',
		'primary_address_country',
		'lawful_basis_source',
		'lead_source',
		'account_name',
		'account_id',
		'opportunity_role',
		'opportunity_role_id',
		'campaign_name',
		'campaign_id',
		'accept_status_name',
		'accept_status_id',
		'event_status_id',
		'event_invite_id',
		'event_accept_status',
	);

	foreach ( $contact_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$contact[ $field ] = $payload[ $field ];
		}
	}

	$query = "contacts.last_name = '{$contact['last_name']}'";

	if ( isset( $contact['last_name'] ) ) {
		$query .= " AND contacts.first_name = '{$contact['first_name']}'";
	}

	$response = $bridge->patch(
		array(
			'method'   => 'get_entry_list',
			'endpoint' => 'Contacts',
		)
	)->submit( array( 'query' => $query ) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$contact_id = $response['data']['entry_list'][0]['id'] ?? null;
	if ( ! empty( $contact_id ) ) {
		$response = $bridge->patch(
			array(
				'name'     => '__suitecrm-' . time(),
				'method'   => 'set_entry',
				'endpoint' => 'Contacts',
			)
		)->submit( array_merge( array( 'id' => $contact_id ), $contact ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$payload['contact_id'] = $contact_id;
		return $payload;
	}

	$response = $bridge->patch(
		array(
			'name'     => '__suitecrm-' . time(),
			'method'   => 'set_entry',
			'endpoint' => 'Contacts',
		)
	)->submit( $contact );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$payload['contact_id'] = $response['data']['id'];
	return $payload;
}
