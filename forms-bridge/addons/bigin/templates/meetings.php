<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Meetings', 'forms-bridge' ),
	'description' => __(
		'Meetings form template. The resulting bridge will convert form submissions into events on the calendar linked to new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/bigin/v2/Events',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'Owner.id',
			'label'       => __( 'Owner', 'forms-bridge' ),
			'descritpion' => __(
				'Email of the owner user of the event',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/bigin/v2/users',
				'finger'   => array(
					'value' => 'users[].id',
					'label' => 'users[].full_name',
				),
			),
			'required'    => true,
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'Event_Title',
			'label'    => __( 'Event title', 'forms-bridge' ),
			'type'     => 'text',
			'required' => true,
			'default'  => __( 'Web Meeting', 'forms-bridge' ),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'All_day',
			'label'   => __( 'Is all day event?', 'forms-bridge' ),
			'type'    => 'boolean',
			'default' => false,
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'duration',
			'label'       => __( 'Duration', 'forms-bridge' ),
			'description' => __( 'Duration in hours', 'forms-bridge' ),
			'type'        => 'number',
			'default'     => '1',
			'min'         => 0,
			'max'         => 24,
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Meetings', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'fields' => array(
			array(
				'name'     => 'First_Name',
				'label'    => __( 'First name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'Last_Name',
				'label'    => __( 'Last name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'Email',
				'label'    => __( 'Email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'  => 'Phone',
				'label' => __( 'Phone', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'     => 'date',
				'label'    => __( 'Date', 'forms-bridge' ),
				'type'     => 'date',
				'required' => true,
			),
			array(
				'name'     => 'hour',
				'label'    => __( 'Hour', 'forms-bridge' ),
				'type'     => 'select',
				'options'  => array(
					array(
						'label' => __( '1 AM', 'forms-bridge' ),
						'value' => '01',
					),
					array(
						'label' => __( '2 AM', 'forms-bridge' ),
						'value' => '02',
					),
					array(
						'label' => __( '3 AM', 'forms-bridge' ),
						'value' => '03',
					),
					array(
						'label' => __( '4 AM', 'forms-bridge' ),
						'value' => '04',
					),
					array(
						'label' => __( '5 AM', 'forms-bridge' ),
						'value' => '05',
					),
					array(
						'label' => __( '6 AM', 'forms-bridge' ),
						'value' => '06',
					),
					array(
						'label' => __( '7 AM', 'forms-bridge' ),
						'value' => '07',
					),
					array(
						'label' => __( '8 AM', 'forms-bridge' ),
						'value' => '08',
					),
					array(
						'label' => __( '9 AM', 'forms-bridge' ),
						'value' => '09',
					),
					array(
						'label' => __( '10 AM', 'forms-bridge' ),
						'value' => '10',
					),
					array(
						'label' => __( '11 AM', 'forms-bridge' ),
						'value' => '11',
					),
					array(
						'label' => __( '12 AM', 'forms-bridge' ),
						'value' => '12',
					),
					array(
						'label' => __( '1 PM', 'forms-bridge' ),
						'value' => '13',
					),
					array(
						'label' => __( '2 PM', 'forms-bridge' ),
						'value' => '14',
					),
					array(
						'label' => __( '3 PM', 'forms-bridge' ),
						'value' => '15',
					),
					array(
						'label' => __( '4 PM', 'forms-bridge' ),
						'value' => '16',
					),
					array(
						'label' => __( '5 PM', 'forms-bridge' ),
						'value' => '17',
					),
					array(
						'label' => __( '6 PM', 'forms-bridge' ),
						'value' => '18',
					),
					array(
						'label' => __( '7 PM', 'forms-bridge' ),
						'value' => '19',
					),
					array(
						'label' => __( '8 PM', 'forms-bridge' ),
						'value' => '20',
					),
					array(
						'label' => __( '9 PM', 'forms-bridge' ),
						'value' => '21',
					),
					array(
						'label' => __( '10 PM', 'forms-bridge' ),
						'value' => '22',
					),
					array(
						'label' => __( '11 PM', 'forms-bridge' ),
						'value' => '23',
					),
					array(
						'label' => __( '12 PM', 'forms-bridge' ),
						'value' => '24',
					),
				),
				'required' => 'true',
			),
			array(
				'name'     => 'minute',
				'label'    => __( 'Minute', 'forms-bridge' ),
				'type'     => 'select',
				'options'  => array(
					array(
						'label' => '00',
						'value' => '00.0',
					),
					array(
						'label' => '05',
						'value' => '05',
					),
					array(
						'label' => '10',
						'value' => '10',
					),
					array(
						'label' => '15',
						'value' => '15',
					),
					array(
						'label' => '20',
						'value' => '20',
					),
					array(
						'label' => '25',
						'value' => '25',
					),
					array(
						'label' => '30',
						'value' => '30',
					),
					array(
						'label' => '35',
						'value' => '35',
					),
					array(
						'label' => '40',
						'value' => '40',
					),
					array(
						'label' => '45',
						'value' => '45',
					),
					array(
						'label' => '50',
						'value' => '50',
					),
					array(
						'label' => '55',
						'value' => '55',
					),
				),
				'required' => true,
			),
			array(
				'name'  => 'Description',
				'label' => __( 'Comments', 'forms-bridge' ),
				'type'  => 'textarea',
			),
		),
	),
	'bridge'      => array(
		'endpoint'  => '/bigin/v2/Events',
		'mutations' => array(
			array(
				array(
					'from' => '?All_day',
					'to'   => 'All_day',
					'cast' => 'boolean',
				),
			),
			array(
				array(
					'from' => 'datetime',
					'to'   => 'date',
					'cast' => 'string',
				),
			),
		),
		'workflow'  => array(
			'date-fields-to-date',
			'event-dates',
			'appointment-participant',
		),
	),
);
