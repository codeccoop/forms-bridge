<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Skip subscription', 'forms-bridge' ),
	'description' => __(
		'Skip subscription if the brevo field is not true',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_brevo_skip_subscription',
	'input'       => array(
		array(
			'name'     => 'brevo',
			'schema'   => array( 'type' => 'boolean' ),
			'required' => true,
		),
	),
	'output'      => array(),
);

function forms_bridge_brevo_skip_subscription( $payload ) {
	if ( $payload['brevo'] != true ) {
		return;
	}

	return $payload;
}
