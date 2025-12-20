<?php
/**
 * Next code client Dolibarr job.
 *
 * @package forms-bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Sets code_client to -1 on the payload to inform Dolibarr to set this field to the
 * next value in the serie on thidparty creation.
 *
 * @param array $payload Bridge payload.
 *
 * @return array
 */
function forms_bridge_dolibarr_next_code_client( $payload ) {
	$payload['code_client'] = -1;
	return $payload;
}

return array(
	'title'       => __( 'Next code client', 'forms-bridge' ),
	'description' => __(
		'Sets code_client to -1 to let Dolibarr fulfill the field with the next value of the serie',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_next_code_client',
	'input'       => array(),
	'output'      => array(
		array(
			'name'   => 'code_client',
			'schema' => array( 'type' => 'integer' ),
		),
	),
);
