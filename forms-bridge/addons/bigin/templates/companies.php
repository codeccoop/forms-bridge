<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Company Contact', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will convert form submissions into contacts linked to company accounts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/bigin/v2/Contacts/upsert',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'Owner.id',
			'label'       => __( 'Owner', 'forms-bridge' ),
			'description' => __(
				'Email of the owner user of the account',
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
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Companies', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'fields' => array(
			array(
				'name'     => 'Account_Name',
				'label'    => __( 'Company name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'  => 'Billing_Street',
				'label' => __( 'Street', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'Billing_Code',
				'label' => __( 'Postal code', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'Billing_City',
				'label' => __( 'City', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'Billing_State',
				'label' => __( 'State', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'Billing_Country',
				'label' => __( 'Country', 'forms-bridge' ),
				'type'  => 'text',
			),
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
				'name'     => 'Title',
				'label'    => __( 'Title', 'forms-bridge' ),
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
				'name'  => 'Description',
				'label' => __( 'Comments', 'forms-bridge' ),
				'type'  => 'textarea',
			),
		),
	),
	'bridge'      => array(
		'endpoint' => '/bigin/v2/Contacts/upsert',
		'workflow' => array( 'account-name' ),
	),
);
