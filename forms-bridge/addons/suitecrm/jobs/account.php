<?php
/**
 * SuiteCRM account job
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Account', 'forms-bridge' ),
	'description' => __( 'Creates an account in SuiteCRM', 'forms-bridge' ),
	'method'      => 'forms_bridge_suitecrm_create_account',
	'input'       => array(
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
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
			'name'   => 'account_type',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'industry',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'annual_revenue',
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
			'name'   => 'email_address_non_primary',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone_fax',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone_office',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone_alternate',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'website',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'ownership',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'employees',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'billing_address_street',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'billing_address_postalcode',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'billing_address_city',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'billing_address_state',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'billing_address_country',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'shipping_address_street',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'shipping_address_street_2',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'shipping_address_street_3',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'shipping_address_street_4',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'shipping_address_postalcode',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'shipping_address_city',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'shipping_address_state',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'shipping_address_country',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'sic_code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'parent_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'parent_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'campaing_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'campaign_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'description',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'account_id',
			'schema' => array( 'type' => 'string' ),
		),
	),
);

/**
 * Creates a new account and add its ID to the payload.
 *
 * @param array       $payload Bridge payload.
 * @param Form_Bridge $bridge Bridge object.
 *
 * @return array
 */
function forms_bridge_suitecrm_create_account( $payload, $bridge ) {
	$account = array(
		'name' => $payload['name'],
	);

	$account_fields = array(
		'description',
		'assigned_user_id',
		'assigned_user_name',
		'account_type',
		'industry',
		'annual_revenue',
		'email',
		'email1',
		'email_address_non_primary',
		'phone_fax',
		'phone_office',
		'phone_alternate',
		'website',
		'ownership',
		'employees',
		'billing_address_street',
		'billing_address_postalcode',
		'billing_address_city',
		'billing_address_state',
		'billing_address_country',
		'shipping_address_street',
		'shipping_address_street_2',
		'shipping_address_street_3',
		'shipping_address_street_4',
		'shipping_address_postalcode',
		'shipping_address_city',
		'shipping_address_state',
		'shipping_address_country',
		'sic_code',
		'parent_id',
		'parent_name',
		'campaign_id',
		'campaign_name',
		'description',
	);

	foreach ( $account_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$account[ $field ] = $payload[ $field ];
		}
	}

	$query = "accounts.name = '{$account['name']}'";

	$response = $bridge->patch(
		array(
			'method'   => 'get_entry_list',
			'endpoint' => 'Accounts',
		)
	)->submit( array( 'query' => $query ) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$account_id = $response['data']['entry_list'][0]['id'] ?? null;
	if ( ! empty( $account_id ) ) {
		$response = $bridge->patch(
			array(
				'method'   => 'set_entry',
				'endpoint' => 'Accounts',
			)
		)->submit( array_merge( array( 'id' => $account_id ), $account ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$payload['account_id'] = $account_id;
		return $payload;
	}

	$response = $bridge->patch(
		array(
			'method'   => 'set_entry',
			'endpoint' => 'Accounts',
		)
	)->submit( $account );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$payload['account_id'] = $response['data']['id'];
	return $payload;
}
