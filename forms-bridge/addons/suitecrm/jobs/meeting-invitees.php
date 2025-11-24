<?php
/**
 * SuiteCRM meeting invitees job
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Meeting invitees', 'forms-bridge' ),
	'description' => __( 'Adds invitees to a SuiteCRM meeting', 'forms-bridge' ),
	'method'      => 'forms_bridge_suitecrm_add_meeting_invitees',
	'input'       => array(
		array(
			'name'     => 'contact_id',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'     => 'assigned_user_id',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
	),
	'output'      => array(
		array(
			'name'   => 'contact_id',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'assigned_user_id',
			'schema' => array( 'type' => 'string' ),
		),
	),
);

/**
 * Adds invitees to a meeting (contact and assigned user).
 *
 * @param array $payload Bridge payload.
 *
 * @return array|WP_Error
 */
function forms_bridge_suitecrm_add_meeting_invitees( $payload ) {
	$invitees = array(
		'contact_id'       => $payload['contact_id'],
		'assigned_user_id' => $payload['assigned_user_id'],
	);

	add_action(
		'forms_bridge_after_submission',
		function ( $bridge, $response, $payload, $attachments ) use ( $invitees ) {
			$meeting_id = $response['data']['id'];

			// Add contact relationship.
			if ( ! empty( $invitees['contact_id'] ) ) {
				$contact_relationship = array(
					'module_id'       => $meeting_id,
					'link_field_name' => 'contacts',
					'related_ids'     => array( $invitees['contact_id'] ),
				);

				$response = $bridge->patch(
					array(
						'method'   => 'set_relationship',
						'endpoint' => 'Meetings',
					)
				)->submit( $contact_relationship );

				if ( is_wp_error( $response ) ) {
					do_action(
						'forms_bridge_on_failure',
						$bridge,
						$response,
						$payload,
						$attachments,
					);
				}
			}

			// Add assigned user relationship.
			if ( ! empty( $invitees['assigned_user_id'] ) ) {
				$user_relationship = array(
					'module_id'       => $meeting_id,
					'link_field_name' => 'users',
					'related_ids'     => array( $invitees['assigned_user_id'] ),
				);

				$response = $bridge->patch(
					array(
						'method'   => 'set_relationship',
						'endpoint' => 'Meetings',
					)
				)->submit( $user_relationship );

				if ( is_wp_error( $response ) ) {
					do_action(
						'forms_bridge_on_failure',
						$bridge,
						$response,
						$payload,
						$attachments,
					);
				}
			}
		},
		10,
		4
	);

	return $payload;
}
