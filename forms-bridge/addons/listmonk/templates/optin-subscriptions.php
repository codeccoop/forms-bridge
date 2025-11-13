<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Opt-in Subscriptions', 'forms-bridge' ),
	'description' => __(
		'Subscription form template. The resulting bridge will convert form submissions into new list subscriptions with a double opt-in confirmation check.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/subscribers',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'status',
			'label'    => __( 'Subscription status', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				array(
					'label' => __( 'Enabled', 'forms-bridge' ),
					'value' => 'enabled',
				),
				array(
					'label' => __( 'Disabled', 'forms-bridge' ),
					'value' => 'blocklisted',
				),
			),
			'required' => true,
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Opt-in subscriptions', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Opt-in subscriptions', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'your-email',
				'label'    => __( 'Your email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'  => 'your-name',
				'label' => __( 'Your name', 'forms-bridge' ),
				'type'  => 'text',
			),
		),
	),
	'backend'     => array(
		'headers' => array(
			array(
				'name'  => 'Content-Type',
				'value' => 'application/json',
			),
			array(
				'name'  => 'Accept',
				'value' => 'application/json',
			),
		),
	),
	'bridge'      => array(
		'method'        => 'POST',
		'endpoint'      => '/api/subscribers',
		'custom_fields' => array(
			array(
				'name'  => 'attribs.locale',
				'value' => '$locale',
			),
			array(
				'name'  => 'preconfirm_subscriptions',
				'value' => '0',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'your-email',
					'to'   => 'email',
					'cast' => 'string',
				),
				array(
					'from' => 'your-name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'preconfirm_subscriptions',
					'to'   => 'preconfirm_subscriptions',
					'cast' => 'boolean',
				),
			),
		),
	),
);
