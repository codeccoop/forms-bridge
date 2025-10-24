<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Prospects', 'forms-bridge' ),
	'description' => __(
		'Lead form template. The resulting bridge will convert form submissions into prospect contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/index.php/thirdparties',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'stcomm_id',
			'label'    => __( 'Prospect status', 'forms-bridge' ),
			'required' => true,
			'type'     => 'select',
			'options'  => array(
				array(
					'label' => __( 'Never contacted', 'forms-bridge' ),
					'value' => '0',
				),
				array(
					'label' => __( 'To contact', 'forms-bridge' ),
					'value' => '1',
				),
				array(
					'label' => __( 'Contact in progress', 'forms-bridge' ),
					'value' => '2',
				),
				array(
					'label' => __( 'Contacted', 'forms-bridge' ),
					'value' => '3',
				),
				array(
					'label' => __( 'Do not contact', 'forms-bridge' ),
					'value' => '-1',
				),
			),
			'default'  => '0',
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Prospects', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Prospects', 'forms-bridge' ),
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
			array(
				'name'  => 'phone',
				'label' => __( 'Phone', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'note_private',
				'label' => __( 'Comments', 'forms-bridge' ),
				'type'  => 'textarea',
			),
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/index.php/thirdparties',
		'custom_fields' => array(
			array(
				'name'  => 'status',
				'value' => '1',
			),
			array(
				'name'  => 'typent_id',
				'value' => '8',
			),
			array(
				'name'  => 'client',
				'value' => '2',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'firstname',
					'to'   => 'name[0]',
					'cast' => 'string',
				),
				array(
					'from' => 'lastname',
					'to'   => 'name[1]',
					'cast' => 'string',
				),
				array(
					'from' => 'name',
					'to'   => 'name',
					'cast' => 'concat',
				),
			),
		),
		'workflow'      => array( 'skip-if-thirdparty-exists', 'next-client-code' ),
	),
);
