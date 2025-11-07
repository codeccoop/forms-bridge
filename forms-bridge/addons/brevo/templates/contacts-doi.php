<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contacts DOI', 'forms-bridge' ),
	'description' => __(
		'Subscription form template. The resulting bridge will convert form submissions into new list subscriptions with a double opt-in confirmation check.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/v3/contacts/doubleOptinConfirmation',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'includeListIds',
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
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'templateId',
			'label'    => __( 'Double opt-in template', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => '/v3/smtp/templates',
				'finger'   => array(
					'value' => 'templates[].id',
					'label' => 'templates[].name',
				),
			),
			'required' => true,
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'redirectionUrl',
			'label'       => __( 'Redirection URL', 'forms-bridge' ),
			'type'        => 'text',
			'description' => __(
				'URL of the web page that user will be redirected to after clicking on the double opt in URL',
				'forms-bridge'
			),
			'required'    => true,
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Contacts DOI', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Contacts DOI', 'forms-bridge' ),
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
		'endpoint'      => '/v3/contacts/doubleOptinConfirmation',
		'custom_fields' => array(
			array(
				'name'  => 'attributes.LANGUAGE',
				'value' => '$locale',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'templateId',
					'to'   => 'templateId',
					'cast' => 'integer',
				),
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
