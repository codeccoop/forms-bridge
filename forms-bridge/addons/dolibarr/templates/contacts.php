<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contacts', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will convert form submissions into contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/index.php/contacts',
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'no_email',
			'label'   => __( 'Subscrive to email', 'forms-bridge' ),
			'type'    => 'boolean',
			'default' => true,
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
				'name'     => 'firstname',
				'label'    => __( 'First name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'lastname',
				'label'    => __( 'Last name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'email',
				'label'    => __( 'Email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/index.php/contacts',
		'custom_fields' => array(
			array(
				'name'  => 'status',
				'value' => '1',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'no_email',
					'to'   => 'no_email',
					'cast' => 'string',
				),
			),
		),
		'workflow'      => array( 'skip-if-contact-exists' ),
	),
);
