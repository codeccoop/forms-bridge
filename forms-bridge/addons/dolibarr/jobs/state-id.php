<?php
/**
 * State ID Dolibarr job.
 *
 * @package forms-bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Gets the state_id value from the state code.
 *
 * @param array                $payload Bridge payload.
 * @param Dolibarr_Form_Bridge $bridge Bridge object.
 *
 * @return array
 */
function forms_bridge_dolibarr_country_id_from_code( $payload, $bridge ) {
	$response = $bridge
		->patch(
			array(
				'name'     => 'dolibarr-get-state-id',
				'method'   => 'GET',
				'endpoint' =>
					'/api/index.php/setup/dictionary/states/byCode/' .
					$payload['state'],
			)
		)
		->submit();

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$payload['state_id'] = $response['data']['id'];
	return $payload;
}

return array(
	'title'       => __( 'State ID', 'forms-bridge' ),
	'description' => __(
		'Gets state_id value from state code and replace it on the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_state_id_from_code',
	'input'       => array(
		array(
			'name'     => 'state',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'state_id',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'state_id',
			'schema' => array( 'type' => 'integer' ),
		),
	),
);
