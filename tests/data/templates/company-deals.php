<?php

return array(
	'title'  => 'Company deals',
	'fields' => array(
		array(
			'name'     => 'company_name',
			'label'    => 'Company',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'name'     => 'country',
			'label'    => 'Country',
			'type'     => 'select',
			'options'  => array(
				array(
					'value' => '1',
					'label' => 'United States',
				),
				array(
					'value' => '7',
					'label' => 'Russia',
				),
				array(
					'value' => '20',
					'label' => 'Egypt',
				),
				array(
					'value' => '27',
					'label' => 'South Africa',
				),
				array(
					'value' => '30',
					'label' => 'Greece',
				),
				array(
					'value' => '31',
					'label' => 'Netherlands',
				),
			),
			'required' => true,
		),
		array(
			'name'  => 'phone',
			'label' => 'Phone',
			'type'  => 'text',
		),
		array(
			'name'  => 'website',
			'label' => 'Website',
			'type'  => 'url',
		),
		array(
			'name'  => 'industry',
			'label' => 'Industry',
			'type'  => 'text',
		),
		array(
			'name'     => 'email',
			'label'    => 'Your email',
			'type'     => 'email',
			'required' => true,
		),
		array(
			'name'     => 'fname',
			'label'    => 'Your first name',
			'type'     => 'text',
			'required' => false,
		),
		array(
			'name'  => 'lname',
			'label' => 'Your last name',
			'type'  => 'text',
		),
	),
);
