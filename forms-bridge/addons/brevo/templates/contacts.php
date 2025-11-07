<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contacts', 'forms-bridge' ),
	'description' => __(
		'Subscription form template. The resulting bridge will convert form submissions into new list subscriptions.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/v3/contacts',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'listIds',
			'endpoint' => '/v3/contacts/lists',
			'label'    => __( 'Segments', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => '/v3/contacts/lists',
				'finger'   => array(
					'value' => 'lists[].id',
					'label' => 'lists[].name',
				),
			),
			'is_multi' => true,
			'required' => true,
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Contacts', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Contacts', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'email',
				'label'    => __( 'Your email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'     => 'fname',
				'label'    => __( 'Your first name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => false,
			),
			array(
				'name'  => 'lname',
				'label' => __( 'Your last name', 'forms-bridge' ),
				'type'  => 'text',
			),
		),
	),
	'backend'     => array(
		'base_url' => 'https://api.brevo.com',
		'headers'  => array(
			array(
				'name'  => 'Accept',
				'value' => 'application/json',
			),
		),
	),
	'bridge'      => array(
		'method'        => 'POST',
		'endpoint'      => '/v3/contacts',
		'custom_fields' => array(
			array(
				'name'  => 'attributes.LANGUAGE',
				'value' => '$locale',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'fname',
					'to'   => 'attributes.FNAME',
					'cast' => 'string',
				),
				array(
					'from' => 'lname',
					'to'   => 'attributes.LNAME',
					'cast' => 'string',
				),
			),
		),
	),
);
