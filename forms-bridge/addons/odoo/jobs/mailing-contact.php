<?php
/**
 * Update mailing contacts Odoo add-on job.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * It performs a search request in order to find some mailing contact in Odoo by email. If found,
 * then updates its list subscriptions and skip the submission. Otherwise returns the payload
 * without changes and let the bridge to continue with the workflow.
 *
 * @param array            $payload Bridge payload.
 * @param Odoo_Form_Bridge $bridge Bridge object.
 *
 * @return array|null|WP_Error
 */
function forms_bridge_odoo_update_mailing_contact( $payload, $bridge ) {
	// Patch the current bridge and dispatch a search request by email to mailing.contacts.
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

	// If no contact is found the response is a 404 Not Found error.
	if ( ! is_wp_error( $response ) ) {
		$contact_id = $response['data']['result'][0];
		$list_ids   = $payload['list_ids'];

		// Dispatch a `write` operation to update the `list_ids` field of the
		// `mailing.contact` model.
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

		// If Odoo returns an error, then return the error as the output of the job.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Otherwise, return an empty payload to abort the bridge submission. All work
		// is done!
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
	'method'      => 'forms_bridge_odoo_update_mailing_contact',
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
