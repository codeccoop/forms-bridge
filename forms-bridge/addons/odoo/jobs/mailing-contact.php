<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_brige_odoo_update_mailing_contact( $payload, $bridge ) {
	$response = $bridge
		->patch(
			array(
				'name'     => 'odoo-search-mailing-contact-by-email',
				'template' => null,
				'method'   => 'search',
				'endpoint' => 'mailing.contact',
			)
		)
		->submit( array( array( 'email', '=', $payload['email'] ) ) );

	if ( ! is_wp_error( $response ) ) {
		$contact_id = $response['data']['result'][0];
		$list_ids   = $payload['list_ids'];

		$response = $bridge
			->patch(
				array(
					'name'     => 'odoo-update-mailing-contact-subscriptions',
					'template' => null,
					'endpoint' => 'mailing.contact',
					'method'   => 'write',
				)
			)
			->submit( array( $contact_id ), array( 'list_ids' => $list_ids ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return;
	}

	return $payload;
}

return array(
	'title'       => __( 'Skip subscription', 'forms-bridge' ),
	'description' => __(
		'Search for a subscribed mailing contact, updates its subscriptions and skips if succeed',
		'forms-bridge'
	),
	'method'      => 'forms_brige_odoo_update_mailing_contact',
	'input'       => array(
		array(
			'name'     => 'email',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
	),
	'output'      => array(
		array(
			'name'   => 'email',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'name',
			'schema' => array( 'type' => 'string' ),
		),
	),
);
