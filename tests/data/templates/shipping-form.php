<?php

return array(
	'title'    => 'Test Shipping Form',
	'fields'   => array(
		array(
			'name'     => 'name',
			'label'    => 'Name',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'name'           => 'email',
			'label'          => 'Email',
			'type'           => 'email',
			'required'       => true,
			'is_conditional' => true,
		),
		array(
			'name'     => 'age',
			'label'    => 'Age',
			'type'     => 'number',
			'required' => true,
		),
		array(
			'name'    => 'gender',
			'label'   => 'Gender',
			'type'    => 'select',
			'options' => array(
				array(
					'value' => 'male',
					'label' => 'Male',
				),
				array(
					'value' => 'female',
					'label' => 'Female',
				),
			),
		),
		array(
			'name'  => 'subscription',
			'label' => 'Subscription',
			'type'  => 'checkbox',
		),
		array(
			'name'        => 'street',
			'label'       => 'Street',
			'type'        => 'text',
			'conditional' => true,
		),
		array(
			'name'        => 'zip',
			'label'       => 'Postal code',
			'type'        => 'text',
			'conditional' => true,
		),
		array(
			'name'        => 'city',
			'label'       => 'City',
			'type'        => 'text',
			'conditional' => true,
		),
	),
	'payloads' => array(
		array(
			'name'         => 'John Doe',
			'email'        => 'johndoe@email.me',
			'age'          => '56',
			'gender'       => 'male',
			'subscription' => false,
		),
		array(
			'name'         => 'John Doe',
			'email'        => 'johndoe@email.me',
			'age'          => '56',
			'gender'       => 'male',
			'subscription' => true,
			'street'       => 'Elm Street',
			'zip'          => '00000',
			'city'         => 'Plugin Town',
		),
	),
);
