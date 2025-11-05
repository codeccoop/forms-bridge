<?php

return array(
	'title'  => 'Subscription Request',
	'fields' => array(
		array(
			'label'    => __( 'Ordered parts', 'forms-bridge' ),
			'name'     => 'ordered_parts',
			'type'     => 'number',
			'required' => true,
			'min'      => 1,
		),
		array(
			'label'    => __( 'Remuneration type', 'forms-bridge' ),
			'name'     => 'remuneration_type',
			'type'     => 'select',
			'required' => true,
			'options'  => array(
				array(
					'value' => 'cash',
					'label' => __( 'Cash', 'forms-bridge' ),
				),
				array(
					'value' => 'wallet',
					'label' => __( 'Wallet', 'forms-bridge' ),
				),
			),
		),
		array(
			'label'    => __( 'First name', 'forms-bridge' ),
			'name'     => 'firstname',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'label'    => __( 'Last name', 'forms-bridge' ),
			'name'     => 'lastname',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'label'    => __( 'ID number', 'forms-bridge' ),
			'name'     => 'vat',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'label'    => __( 'Nationality', 'forms-bridge' ),
			'name'     => 'country',
			'type'     => 'select',
			'options'  => array(
				array(
					'value' => 'es',
					'label' => 'Spain',
				),
				array(
					'value' => 'fr',
					'label' => 'France',
				),
				array(
					'value' => 'de',
					'label' => 'Germany',
				),
			),
			'required' => true,
		),
		array(
			'label'    => __( 'Email', 'forms-bridge' ),
			'name'     => 'email',
			'type'     => 'email',
			'required' => true,
		),
		array(
			'label'    => __( 'Phone', 'forms-bridge' ),
			'name'     => 'phone',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'label'    => __( 'Address', 'forms-bridge' ),
			'name'     => 'address',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'label'    => __( 'Zip code', 'forms-bridge' ),
			'name'     => 'zip_code',
			'type'     => 'text',
			'required' => true,
		),
		array(
			'label'    => __( 'City', 'forms-bridge' ),
			'name'     => 'city',
			'type'     => 'text',
			'required' => true,
		),
	),
);
