<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Subscription', 'forms-bridge' ),
	'description' => __(
		'Subscription form template. The resulting bridge will convert form submissions into new list subscriptions.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/3.0/lists/{list_id}/members',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'status',
			'label'    => __( 'Subscription status', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				array(
					'label' => __( 'Subscribed', 'forms-bridge' ),
					'value' => 'subscribed',
				),
				array(
					'label' => __( 'Unsubscribed', 'forms-bridge' ),
					'value' => 'unsubscribed',
				),
				array(
					'label' => __( 'Pending', 'forms-bridge' ),
					'value' => 'pending',
				),
				array(
					'label' => __( 'Cleaned', 'forms-bridge' ),
					'value' => 'cleand',
				),
				array(
					'label' => __( 'Transactional', 'forms-bridge' ),
					'value' => 'transactional',
				),
			),
			'default'  => 'subscribed',
			'required' => true,
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'tags',
			'label'       => __( 'Subscription tags', 'forms-bridge' ),
			'description' => __(
				'Tag names separated by commas',
				'forms-bridge'
			),
			'type'        => 'text',
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Subscription', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Subscription', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'email_address',
				'label'    => __( 'Your email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'  => 'fname',
				'label' => __( 'Your first name', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'lname',
				'label' => __( 'Your last name', 'forms-bridge' ),
				'type'  => 'text',
			),
		),
	),
	'bridge'      => array(
		'method'        => 'POST',
		'endpoint'      => '/3.0/lists/{list_id}/members',
		'custom_fields' => array(
			array(
				'name'  => 'language',
				'value' => '$locale',
			),
			array(
				'name'  => 'ip_signup',
				'value' => '$ip_address',
			),
			array(
				'name'  => 'timestamp_signup',
				'value' => '$iso_date',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'fname',
					'to'   => 'merge_fields.FNAME',
					'cast' => 'string',
				),
				array(
					'from' => 'lname',
					'to'   => 'merge_fields.LNAME',
					'cast' => 'string',
				),
			),
		),
	),
);
