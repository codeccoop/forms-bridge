<?php

return array(
	'title'  => 'Appointments',
	'fields' => array(
		array(
			'name'     => 'firstname',
			'label'    => 'First name',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'name'     => 'lastname',
			'label'    => 'Last name',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'name'     => 'email',
			'label'    => 'Email',
			'type'     => 'email',
			'required' => true,
		),
		array(
			'name'     => 'date',
			'label'    => 'Date',
			'type'     => 'date',
			'required' => true,
		),
		array(
			'name'     => 'hour',
			'label'    => 'Hour',
			'type'     => 'select',
			'required' => true,
			'options'  => array(
				array(
					'label' => '1 AM',
					'value' => '01',
				),
				array(
					'label' => '2 AM',
					'value' => '02',
				),
				array(
					'label' => '3 AM',
					'value' => '03',
				),
				array(
					'label' => '4 AM',
					'value' => '04',
				),
				array(
					'label' => '5 AM',
					'value' => '05',
				),
				array(
					'label' => '6 AM',
					'value' => '06',
				),
				array(
					'label' => '7 AM',
					'value' => '07',
				),
				array(
					'label' => '8 AM',
					'value' => '08',
				),
				array(
					'label' => '9 AM',
					'value' => '09',
				),
				array(
					'label' => '10 AM',
					'value' => '10',
				),
				array(
					'label' => '11 AM',
					'value' => '11',
				),
				array(
					'label' => '12 AM',
					'value' => '12',
				),
				array(
					'label' => '1 PM',
					'value' => '13',
				),
				array(
					'label' => '2 PM',
					'value' => '14',
				),
				array(
					'label' => '3 PM',
					'value' => '15',
				),
				array(
					'label' => '4 PM',
					'value' => '16',
				),
				array(
					'label' => '5 PM',
					'value' => '17',
				),
				array(
					'label' => '6 PM',
					'value' => '18',
				),
				array(
					'label' => '7 PM',
					'value' => '19',
				),
				array(
					'label' => '8 PM',
					'value' => '20',
				),
				array(
					'label' => '9 PM',
					'value' => '21',
				),
				array(
					'label' => '10 PM',
					'value' => '22',
				),
				array(
					'label' => '11 PM',
					'value' => '23',
				),
				array(
					'label' => '12 PM',
					'value' => '24',
				),
			),
		),
		array(
			'name'     => 'minute',
			'label'    => 'Minute',
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
);
