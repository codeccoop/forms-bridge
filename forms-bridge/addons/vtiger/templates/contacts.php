<?php
/**
 * Vtiger Contacts template.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contacts', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will convert form submissions into Vtiger contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Contacts', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'Contacts',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'assigned_user_id',
			'label'       => __( 'Assigned User', 'forms-bridge' ),
			'description' => __(
				'User to assign the contact to',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => 'Users',
				'finger'   => array(
					'value' => 'result.[].id',
					'label' => 'result.[].label',
				),
			),
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'leadsource',
			'label'       => __( 'Lead Source', 'forms-bridge' ),
			'description' => __(
				'Source of the contact',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				array(
					'value' => 'Web Site',
					'label' => __( 'Web Site', 'forms-bridge' ),
				),
				array(
					'value' => 'Cold Call',
					'label' => __( 'Cold Call', 'forms-bridge' ),
				),
				array(
					'value' => 'Email',
					'label' => __( 'Email', 'forms-bridge' ),
				),
				array(
					'value' => 'Word of mouth',
					'label' => __( 'Word of Mouth', 'forms-bridge' ),
				),
				array(
					'value' => 'Campaign',
					'label' => __( 'Campaign', 'forms-bridge' ),
				),
				array(
					'value' => 'Other',
					'label' => __( 'Other', 'forms-bridge' ),
				),
			),
			'default'     => 'Web Site',
		),
	),
	'bridge'      => array(
		'endpoint'      => 'Contacts',
		'method'        => 'create',
		'custom_fields' => array(
			array(
				'name'  => 'leadsource',
				'value' => 'Web Site',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'firstname',
					'to'   => 'firstname',
					'cast' => 'string',
				),
				array(
					'from' => 'lastname',
					'to'   => 'lastname',
					'cast' => 'string',
				),
				array(
					'from' => 'email',
					'to'   => 'email',
					'cast' => 'string',
				),
				array(
					'from' => '?phone',
					'to'   => 'phone',
					'cast' => 'string',
				),
				array(
					'from' => '?mobile',
					'to'   => 'mobile',
					'cast' => 'string',
				),
				array(
					'from' => '?title',
					'to'   => 'title',
					'cast' => 'string',
				),
				array(
					'from' => '?department',
					'to'   => 'department',
					'cast' => 'string',
				),
				array(
					'from' => '?description',
					'to'   => 'description',
					'cast' => 'string',
				),
				array(
					'from' => '?address',
					'to'   => 'mailingstreet',
					'cast' => 'string',
				),
				array(
					'from' => '?city',
					'to'   => 'mailingcity',
					'cast' => 'string',
				),
				array(
					'from' => '?state',
					'to'   => 'mailingstate',
					'cast' => 'string',
				),
				array(
					'from' => '?postal_code',
					'to'   => 'mailingzip',
					'cast' => 'string',
				),
				array(
					'from' => '?country',
					'to'   => 'mailingcountry',
					'cast' => 'string',
				),
				array(
					'from' => '?leadsource',
					'to'   => 'leadsource',
					'cast' => 'string',
				),
				array(
					'from' => '?assigned_user_id',
					'to'   => 'assigned_user_id',
					'cast' => 'string',
				),
			),
		),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'First Name', 'forms-bridge' ),
				'name'     => 'firstname',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Last Name', 'forms-bridge' ),
				'name'     => 'lastname',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Email', 'forms-bridge' ),
				'name'     => 'email',
				'type'     => 'email',
				'required' => true,
			),
			array(
				'label' => __( 'Phone', 'forms-bridge' ),
				'name'  => 'phone',
				'type'  => 'tel',
			),
			array(
				'label' => __( 'Title', 'forms-bridge' ),
				'name'  => 'title',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Description', 'forms-bridge' ),
				'name'  => 'description',
				'type'  => 'textarea',
			),
		),
	),
);
