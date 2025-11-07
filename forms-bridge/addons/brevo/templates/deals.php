<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_country_phone_codes;

return array(
	'title'       => __( 'Deals', 'forms-bridge' ),
	'description' => __(
		'Leads form templates. The resulting bridge will convert form submissions into deals on the sales pipeline linked new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/v3/crm/deals',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'deal_name',
			'label'    => __( 'Deal name', 'forms-bridge' ),
			'type'     => 'text',
			'required' => true,
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'deal_owner',
			'label'       => __( 'Owner email', 'forms-bridge' ),
			'description' => __(
				'Email of the owner user of the deal',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/v3/organization/invited/users',
				'finger'   => 'users[].email',
			),
			'required'    => true,
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'pipeline',
			'label'   => __( 'Pipeline', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				'endpoint' => '/v3/crm/pipeline/details/all',
				'finger'   => array(
					'value' => '[].pipeline',
					'label' => '[].pipeline_name',
				),
			),
		),
		array(
			'ref'   => '#bridge/custom_fields[]',
			'name'  => 'amount',
			'label' => __( 'Deal amount', 'forms-bridge' ),
			'type'  => 'number',
			'min'   => 0,
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Deals', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Deals', 'forms-bridge' ),
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
	'bridge'      => array(
		'method'        => 'POST',
		'endpoint'      => '/v3/crm/deals',
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
			array(
				array(
					'from' => 'deal_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => '?pipeline',
					'to'   => 'attributes.pipeline',
					'cast' => 'string',
				),
				array(
					'from' => 'deal_owner',
					'to'   => 'attributes.deal_owner',
					'cast' => 'string',
				),
				array(
					'from' => '?amount',
					'to'   => 'attributes.amount',
					'cast' => 'number',
				),
			),
		),
		'workflow'      => array( 'linked-contact' ),
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
);
