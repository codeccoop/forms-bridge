<?php
/**
 * SuiteCRM skip contact if exists job
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Skip contact', 'forms-bridge' ),
	'description' => __( 'Searches for existing contacts by name and skip duplications', 'forms-bridge' ),
	'method'      => 'forms_bridge_suitecrm_skip_contact',
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
	),
	'output'      => array(
		array(
			'name'   => 'last_name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'first_name',
			'schema' => array( 'type' => 'string' ),
		),
	),
);

/**
 * Look up existing contacts by name and skips bridge submission if found.
 *
 * @param array                $payload Bridge payload.
 * @param SuiteCRM_Form_Bridge $bridge Bridge object.
 */
function forms_bridge_suitecrm_skip_contact( $payload, $bridge ) {
	$query = "contacts.last_name = '{$payload['last_name']}'";

	if ( isset( $payload['first_name'] ) ) {
		$query .= " AND contacts.first_name = '{$payload['first_name']}'";
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
		$result = forms_bridge_suitecrm_create_contact( $payload, $bridge );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return;
	}

	return $payload;
}
