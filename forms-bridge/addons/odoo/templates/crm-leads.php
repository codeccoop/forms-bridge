<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'CRM Leads', 'forms-bridge' ),
	'description' => __(
		'Lead form template. The resulting bridge will convert form submissions into leads linked to new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'CRM Leads', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'crm.lead',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'user_id',
			'label'       => __( 'Owner', 'forms-bridge' ),
			'description' => __(
				'Name of the owner user of the lead',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => 'res.users',
				'finger'   => array(
					'value' => 'result.[].id',
					'label' => 'result.[].name',
				),
			),
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'lead_name',
			'label'    => __( 'Lead name', 'forms-bridge' ),
			'type'     => 'text',
			'required' => true,
			'default'  => __( 'Web Lead', 'forms-bridge' ),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'priority',
			'label'   => __( 'Priority', 'forms-bridge' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 3,
			'default' => 1,
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'expected_revenue',
			'label'   => __( 'Expected revenue', 'forms-bridge' ),
			'type'    => 'number',
			'min'     => 0,
			'default' => 0,
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'tag_ids',
			'label'    => __( 'Lead tags', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => 'crm.tag',
				'finger'   => array(
					'value' => 'result.[].id',
					'label' => 'result.[].name',
				),
			),
			'is_multi' => true,
		),
	),
	'bridge'      => array(
		'endpoint'  => 'crm.lead',
		'mutations' => array(
			array(
				array(
					'from' => '?user_id',
					'to'   => 'user_id',
					'cast' => 'integer',
				),
				array(
					'from' => 'contact_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => '?priority',
					'to'   => 'priority',
					'cast' => 'string',
				),
				array(
					'from' => '?expected_revenue',
					'to'   => 'expected_revenue',
					'cast' => 'number',
				),
			),
			array(
				array(
					'from' => 'lead_name',
					'to'   => 'name',
					'cast' => 'string',
				),
			),
		),
		'workflow'  => array( 'crm-contact' ),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'Your name', 'forms-bridge' ),
				'name'     => 'contact_name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Your email', 'forms-bridge' ),
				'name'     => 'email',
				'type'     => 'email',
				'required' => true,
			),
			array(
				'label' => __( 'Your phone', 'forms-bridge' ),
				'name'  => 'phone',
				'type'  => 'text',
			),
			array(
				'label'    => __( 'Comments', 'forms-bridge' ),
				'name'     => 'description',
				'type'     => 'textarea',
				'required' => true,
			),
		),
	),
);
