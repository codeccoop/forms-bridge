<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Skip submission', 'forms-bridge' ),
	'description' => __(
		'Skip submission if condition is not truthy',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_job_skip_if_not_condition',
	'input'       => array(
		array(
			'name'     => 'condition',
			'schema'   => array( 'type' => 'boolean' ),
			'required' => true,
		),
	),
	'output'      => array(),
);

function forms_bridge_job_skip_if_not_condition( $payload ) {
	if ( empty( $payload['condition'] ) ) {
		return;
	}

	return $payload;
}
