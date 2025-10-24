<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Company Appointments', 'forms-bridge' ),
	'description' => __(
		'Appointments form template. The resulting bridge will convert form submissions into events on the calendar linked to new companies.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/index.php/agendaevents',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'userownerid',
			'label'       => __( 'Owner', 'forms-bridge' ),
			'description' => __( 'Host user of the event', 'forms-bridge' ),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/api/index.php/users',
				'finger'   => array(
					'value' => '[].id',
					'label' => '[].email',
				),
			),
			'required'    => true,
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'type_code',
			'label'    => __( 'Event type', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => '/api/index.php/setup/dictionary/event_types',
				'finger'   => array(
					'value' => '[].code',
					'label' => '[].label',
				),
			),
			'required' => true,
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'label',
			'label'    => __( 'Event label', 'forms-bridge' ),
			'type'     => 'text',
			'required' => true,
			'default'  => __( 'Web appointment', 'forms-bridge' ),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'fulldayevent',
			'label'   => __( 'Is all day event?', 'forms-bridge' ),
			'type'    => 'boolean',
			'default' => false,
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'duration',
			'label'   => __( 'Duration (Hours)', 'forms-bridge' ),
			'type'    => 'number',
			'default' => 1,
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'client',
			'label'    => __( 'Thirdparty status', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				array(
					'value' => '1',
					'label' => __( 'Client', 'forms-bridge' ),
				),
				array(
					'value' => '2',
					'label' => __( 'Prospect', 'forms-bridge' ),
				),
				array(
					'value' => '3',
					'label' => __( 'Client/Prospect', 'forms-bridge' ),
				),
				array(
					'value' => '0',
					'label' => __(
						'Neither customer nor supplier',
						'forms-bridge'
					),
				),
			),
			'required' => true,
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'typent_id',
			'label'   => __( 'Company type', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				array(
					'label' => __( 'Large company', 'forms-bridge' ),
					'value' => '2',
				),
				array(
					'label' => __( 'Medium company', 'forms-bridge' ),
					'value' => '3',
				),
				array(
					'label' => __( 'Small company', 'forms-bridge' ),
					'value' => '4',
				),
				array(
					'label' => __( 'Governmental', 'forms-bridge' ),
					'value' => '5',
				),
				array(
					'label' => __( 'Startup', 'forms-bridge' ),
					'value' => '1',
				),
				array(
					'label' => __( 'Other', 'forms-bridge' ),
					'value' => '100',
				),
			),
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Appointments', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'fields' => array(
			array(
				'name'     => 'company_name',
				'label'    => __( 'Company name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
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
				'name'     => 'contact_email',
				'label'    => __( 'Email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'     => 'poste',
				'label'    => __( 'Job position', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
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
				'required' => true,
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
			),
			array(
				'name'     => 'minute',
				'label'    => __( 'Minute', 'forms-bridge' ),
				'type'     => 'select',
				'required' => true,
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
			),
		),
	),
	'bridge'      => array(
		'endpoint'  => '/api/index.php/agendaevents',
		'mutations' => array(
			array(
				array(
					'from' => 'company_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => '?duration',
					'to'   => 'duration',
					'cast' => 'number',
				),
			),
			array(
				array(
					'from' => 'datetime',
					'to'   => 'date',
					'cast' => 'string',
				),
			),
			array(
				array(
					'from' => 'contact_email',
					'to'   => 'email',
					'cast' => 'string',
				),
			),
			array(),
		),
		'workflow'  => array(
			'date-fields-to-date',
			'appointment-dates',
			'contact-socid',
			'appointment-attendee',
		),
	),
);
