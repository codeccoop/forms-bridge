<?php
/**
 * SuiteCRM Leads template.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Leads', 'forms-bridge' ),
	'description' => __(
		'Lead capture form template. The resulting bridge will convert form submissions into SuiteCRM leads.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Leads', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'Leads',
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'assigned_user_id',
			'label'   => __( 'Assigned User', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				'endpoint' => 'Users',
				'finger'   => array(
					'value' => 'entry_list[].id',
					'label' => 'entry_list[].name_value_list.name.value',
				),
			),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'status',
			'label'   => __( 'Lead Status', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				array(
					'value' => 'New',
					'label' => __( 'New', 'forms-bridge' ),
				),
				array(
					'value' => 'Assigned',
					'label' => __( 'Assigned', 'forms-bridge' ),
				),
				array(
					'value' => 'In Process',
					'label' => __( 'In Process', 'forms-bridge' ),
				),
				array(
					'value' => 'Converted',
					'label' => __( 'Converted', 'forms-bridge' ),
				),
				array(
					'value' => 'Recycled',
					'label' => __( 'Recycled', 'forms-bridge' ),
				),
				array(
					'value' => 'Dead',
					'label' => __( 'Dead', 'forms-bridge' ),
				),
			),
			'default' => 'New',
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'lead_source',
			'label'   => __( 'Lead Source', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				array(
					'value' => 'Web Site',
					'label' => __( 'Web Site', 'forms-bridge' ),
				),
				array(
					'value' => 'Cold Call',
					'label' => __( 'Cold Call', 'forms-bridge' ),
				),
				array(
					'value' => 'Existing Customer',
					'label' => __( 'Existing Customer', 'forms-bridge' ),
				),
				array(
					'value' => 'Employee',
					'label' => __( 'Employee', 'forms-bridge' ),
				),
				array(
					'value' => 'Partner',
					'label' => __( 'Partner', 'forms-bridge' ),
				),
				array(
					'value' => 'Public Relations',
					'label' => __( 'Public Relations', 'forms-bridge' ),
				),
				array(
					'value' => 'Email',
					'label' => __( 'Email', 'forms-bridge' ),
				),
				array(
					'value' => 'Direct Mail',
					'label' => __( 'Direct Mail', 'forms-bridge' ),
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
					'value' => 'Conference',
					'label' => __( 'Conference', 'forms-bridge' ),
				),
				array(
					'value' => 'Trade Show',
					'label' => __( 'Trade Show', 'forms-bridge' ),
				),
				array(
					'value' => 'Other',
					'label' => __( 'Other', 'forms-bridge' ),
				),
			),
			'default' => 'Web Site',
		),
	),
	'bridge'      => array(
		'endpoint' => 'Leads',
		'method'   => 'set_entry',
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'First Name', 'forms-bridge' ),
				'name'     => 'first_name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Last Name', 'forms-bridge' ),
				'name'     => 'last_name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Email', 'forms-bridge' ),
				'name'     => 'email1',
				'type'     => 'email',
				'required' => true,
			),
			array(
				'label' => __( 'Phone', 'forms-bridge' ),
				'name'  => 'phone_work',
				'type'  => 'tel',
			),
			array(
				'label' => __( 'Company', 'forms-bridge' ),
				'name'  => 'account_name',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Title', 'forms-bridge' ),
				'name'  => 'title',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Website', 'forms-bridge' ),
				'name'  => 'website',
				'type'  => 'url',
			),
			array(
				'label' => __( 'Message', 'forms-bridge' ),
				'name'  => 'description',
				'type'  => 'textarea',
			),
		),
	),
);
