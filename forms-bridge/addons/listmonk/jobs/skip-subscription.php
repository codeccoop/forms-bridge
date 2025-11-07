<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Skip subscription', 'forms-bridge' ),
	'description' => __(
		'Skip subscription if the listmonk field is not true',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_listmonk_skip_subscription',
	'input'       => array(
		array(
			'name'     => 'listmonk',
			'schema'   => array( 'type' => 'boolean' ),
			'required' => true,
		),
	),
	'output'      => array(),
);

function forms_bridge_listmonk_skip_subscription( $payload ) {
	if ( $payload['listmonk'] != true ) {
		return;
	}

	return $payload;
}
