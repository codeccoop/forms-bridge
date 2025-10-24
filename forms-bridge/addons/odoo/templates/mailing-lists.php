<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Mailing Lists', 'forms-bridge' ),
	'description' => __(
		'Subscription form template. The resulting bridge will convert form submissions into subscriptions to mailing lists.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Mailing Lists', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'mailing.contact',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'list_ids',
			'label'    => __( 'Mailing lists', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => 'mailing.list',
				'finger'   => array(
					'value' => 'result[].id',
					'label' => 'result[].name',
				),
			),
			'is_multi' => true,
			'required' => true,
		),
	),
	'bridge'      => array(
		'endpoint'  => 'mailing.contact',
		'workflow'  => array( 'mailing-contact' ),
		'mutations' => array(
			array(
				array(
					'from' => 'first_name',
					'to'   => 'name[0]',
					'cast' => 'copy',
				),
				array(
					'from' => 'last_name',
					'to'   => 'name[1]',
					'cast' => 'copy',
				),
				array(
					'from' => 'name',
					'to'   => 'name',
					'cast' => 'concat',
				),
			),
		),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'First name', 'forms-bridge' ),
				'name'     => 'first_name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Last name', 'forms-bridge' ),
				'name'     => 'last_name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Email', 'forms-bridge' ),
				'name'     => 'email',
				'type'     => 'email',
				'required' => true,
			),
		),
	),
);
