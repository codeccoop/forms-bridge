<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_dolibarr_next_project_ref( $payload, $bridge ) {
	$project_ref = forms_bridge_dolibarr_get_next_project_ref(
		$payload,
		$bridge
	);

	if ( is_wp_error( $project_ref ) ) {
		return $project_ref;
	}

	$payload['ref'] = $project_ref;
	return $payload;
}

return array(
	'title'       => __( 'Next project ref', 'forms-brige' ),
	'description' => __( 'Query for the next valid project ref', 'forms-bridge' ),
	'method'      => 'forms_bridge_dolibarr_next_project_ref',
	'input'       => array(),
	'output'      => array(
		array(
			'name'   => 'ref',
			'schema' => array( 'type' => 'string' ),
		),
	),
);
