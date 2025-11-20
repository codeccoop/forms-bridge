<?php
/**
 * Bigin contacts bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contacts', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will convert form submissions into contacts.',
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
			'label'       => __( 'Owner ID', 'forms-bridge' ),
			'description' => __(
				'ID of the owner user of the contact',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/bigin/v2/users',
				'finger'   => array(
					'value' => 'users[].zuid',
					'label' => 'users[].full_name',
				),
			),
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Contacts', 'forms-bridge' ),
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
				'name'  => 'Description',
				'label' => __( 'Comments', 'forms-bridge' ),
				'type'  => 'textarea',
			),
		),
	),
	'bridge'      => array(
		'endpoint'  => '/bigin/v2/Contacts/upsert',
		'mutations' => array(
			array(
				array(
					'from' => '?Owner.id',
					'to'   => 'Owner.id',
					'cast' => 'string',
				),
			),
		),
	),
);
