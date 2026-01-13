<?php
/**
 * Next project ref Dolibarr job.
 *
 * @package forms-bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * It queries the next valid project ref and sets its value as the 'ref' attribute of
 * the payload.
 *
 * @param array                $payload Bridge payload.
 * @param Dolibarr_Form_Bridge $bridge Bridge object.
 *
 * @return array
 */
function forms_bridge_dolibarr_next_project_ref( $payload, $bridge ) {
	$project_ref = forms_bridge_dolibarr_get_next_project_ref( $payload, $bridge );

	if ( is_wp_error( $project_ref ) ) {
		return $project_ref;
	}

	$payload['ref'] = $project_ref;
	return $payload;
}

return array(
	'title'       => __( 'Next project ref', 'forms-bridge' ),
	'description' => __(
		'Query the next valid project ref',
		'forms-bridge',
	),
	'method'      => 'forms_bridge_dolibarr_next_project_ref',
	'input'       => array(),
	'output'      => array(
		array(
			'name'   => 'ref',
			'schema' => array( 'type' => 'string' ),
		),
	),
);
