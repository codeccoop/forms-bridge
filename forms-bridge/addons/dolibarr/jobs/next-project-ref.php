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
 * Sets ref to -1 on the payload to inform Dolibarr to set this field to the next
 * project ref in hte serie on project creation.
 *
 * @param array $payload Bridge payload.
 *
 * @return array
 */
function forms_bridge_dolibarr_next_project_ref( $payload ) {
	$payload['ref'] = -1;
	return $payload;
}

return array(
	'title'       => __( 'Next project ref', 'forms-bridge' ),
	'description' => __(
		'Sets ref to -1 to let Dolibarr fulfill the field with the next value of the serie',
		'forms-bridge',
	),
	'method'      => 'forms_bridge_dolibarr_next_project_ref',
	'input'       => array(),
	'output'      => array(
		array(
			'name'   => 'ref',
			'schema' => array( 'type' => 'integer' ),
		),
	),
);
