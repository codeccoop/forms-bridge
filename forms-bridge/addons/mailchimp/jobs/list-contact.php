<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Audience subscription', 'forms-bridge' ),
	'description' => __( 'Subscribe a new user to an audience', 'forms-bridge' ),
	'method'      => 'forms_bridge_mailchimp_audience_subscription',
	'input'       => array(
		array(
			'name'     => 'list_id',
			'required' => true,
			'schema'   => array( 'type' => 'string' ),
		),
		array(
			'name'     => 'email_address',
			'required' => true,
			'schema'   => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'status',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'email_type',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'interests',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => true,
			),
		),
		array(
			'name'   => 'language',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'vip',
			'schema' => array( 'type' => 'boolean' ),
		),
		array(
			'name'   => 'location',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'latitude'  => array( 'type' => 'number' ),
					'longitude' => array( 'type' => 'number' ),
				),
				'additionalProperties' => false,
			),
		),
		array(
			'name'   => 'marketing_permissions',
			'schema' => array(
				'type'            => 'array',
				'items'           => array(
					'type'       => 'object',
					'properties' => array(
						'marketing_permission_id' => array( 'type' => 'string' ),
						'enabled'                 => array( 'type' => 'boolean' ),
					),
				),
				'additionalItems' => true,
			),
		),
		array(
			'name'   => 'ip_signup',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'timestamp_signup',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'ip_opt',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'timestamp_opt',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'tags',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'string' ),
				'additionalItems' => true,
			),
		),
	),
	'output'      => array(
		array(
			'name'   => 'email_address',
			'schema' => array( 'type' => 'string' ),
		),
	),
);

function forms_bridge_mailchimp_audience_subscription( $payload, $bridge ) {
	$contact = array(
		'email_address' => $payload['email_address'],
	);

	$contact_fields = array(
		'status',
		'email_type',
		'interests',
		'language',
		'vip',
		'location',
		'marketing_permissions',
		'ip_signup',
		'ip_opt',
		'timestamp_opt',
		'tags',
		'merge_fields',
	);

	foreach ( $contact_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$contact[ $field ] = $payload[ $field ];
		}
	}

	$response = $bridge
		->patch(
			array(
				'endpoint' => "/3.0/lists/{$payload['list_id']}/members",
				'method'   => 'POST',
				'name'     => 'mailchimp-subscribe-member-to-list',
			)
		)
		->submit( $contact );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$payload['email_address'] = $response['data']['email_address'];
	return $payload;
}
