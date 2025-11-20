<?php
/**
 * Zoho CRM company leads bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Company Leads', 'forms-bridge' ),
	'description' => __(
		'Lead form template. The resulting bridge will convert form submissions into company leads.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/crm/v7/Leads/upsert',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'Owner.id',
			'label'       => __( 'Owner', 'forms-bridge' ),
			'description' => __(
				'Email of the owner user of the deal',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/crm/v7/users',
				'finger'   => array(
					'value' => 'users[].zuid',
					'label' => 'users[].full_name',
				),
			),
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'Lead_Source',
			'label'       => __( 'Lead source', 'forms-bridge' ),
			'description' => __(
				'Label to identify your website sourced leads',
				'forms-bridge'
			),
			'type'        => 'text',
			'default'     => 'WordPress',
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'Lead_Status',
			'label'   => __( 'Lead status', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				array(
					'label' => __( 'Not Contacted', 'forms-bridge' ),
					'value' => 'Not Connected',
				),
				array(
					'label' => __( 'Qualified', 'forms-bridge' ),
					'value' => 'Qualified',
				),
				array(
					'label' => __( 'Not qualified', 'forms-bridge' ),
					'value' => 'Not Qualified',
				),
				array(
					'label' => __( 'Pre-qualified', 'forms-bridge' ),
					'value' => 'Pre-Qualified',
				),
				array(
					'label' => __( 'Attempted to Contact', 'forms-bridge' ),
					'value' => 'New Lead',
				),
				array(
					'label' => __( 'Contact in Future', 'forms-bridge' ),
					'value' => 'Connected',
				),
				array(
					'label' => __( 'Junk Lead', 'forms-bridge' ),
					'value' => 'Junk Lead',
				),
				array(
					'label' => __( 'Lost Lead', 'forms-bridge' ),
					'value' => 'Lost Lead',
				),
			),
			'default' => 'Not Contacted',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'Tag',
			'label'       => __( 'Lead tags', 'forms-bridge' ),
			'description' => __(
				'Tag names separated by commas',
				'forms-bridge'
			),
			'type'        => 'text',
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Company Leads', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'fields' => array(
			array(
				'name'     => 'Company',
				'label'    => __( 'Company', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'  => 'Street',
				'label' => __( 'Street', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'Zip_Code',
				'label' => __( 'Postal code', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'City',
				'label' => __( 'City', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'State',
				'label' => __( 'State', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'Country',
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
				'name'  => 'Title',
				'label' => __( 'Job position', 'forms-bridge' ),
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
		'endpoint'  => '/crm/v7/Leads/upsert',
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
