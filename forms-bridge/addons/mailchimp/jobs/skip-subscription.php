<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Skip subscription', 'forms-bridge' ),
	'description' => __(
		'Skip subscription if the mailchimp field is not true',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_mailchimp_skip_subscription',
	'input'       => array(
		array(
			'name'     => 'mailchimp',
			'schema'   => array( 'type' => 'boolean' ),
			'required' => true,
		),
	),
	'output'      => array(),
);

function forms_bridge_mailchimp_skip_subscription( $payload ) {
	if ( $payload['mailchimp'] != true ) {
		return;
	}

	return $payload;
}
