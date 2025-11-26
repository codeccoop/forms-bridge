<?php
/**
 * Vtiger Accounts template.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Accounts', 'forms-bridge' ),
	'description' => __(
		'Account form bridge template. The resulting bridge will convert form submissions into Vtiger accounts (organizations).',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Accounts', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'Contacts',
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'assigned_user_id',
			'label'   => __( 'Assigned User', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				'endpoint' => 'Users',
				'finger'   => array(
					'value' => 'result[].id',
					'label' => 'result[].user_name',
				),
			),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'accounttype',
			'label'   => __( 'Account Type', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				array(
					'value' => 'Analyst',
					'label' => __( 'Analyst', 'forms-bridge' ),
				),
				array(
					'value' => 'Competitor',
					'label' => __( 'Competitor', 'forms-bridge' ),
				),
				array(
					'value' => 'Customer',
					'label' => __( 'Customer', 'forms-bridge' ),
				),
				array(
					'value' => 'Integrator',
					'label' => __( 'Integrator', 'forms-bridge' ),
				),
				array(
					'value' => 'Investor',
					'label' => __( 'Investor', 'forms-bridge' ),
				),
				array(
					'value' => 'Partner',
					'label' => __( 'Partner', 'forms-bridge' ),
				),
				array(
					'value' => 'Press',
					'label' => __( 'Press', 'forms-bridge' ),
				),
				array(
					'value' => 'Prospect',
					'label' => __( 'Prospect', 'forms-bridge' ),
				),
				array(
					'value' => 'Reseller',
					'label' => __( 'Reseller', 'forms-bridge' ),
				),
				array(
					'value' => 'Other',
					'label' => __( 'Other', 'forms-bridge' ),
				),
			),
			'default' => 'Prospect',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'industry',
			'label'       => __( 'Industry', 'forms-bridge' ),
			'description' => __(
				'Industry sector',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				array(
					'value' => 'Apparel',
					'label' => __( 'Apparel', 'forms-bridge' ),
				),
				array(
					'value' => 'Banking',
					'label' => __( 'Banking', 'forms-bridge' ),
				),
				array(
					'value' => 'Biotechnology',
					'label' => __( 'Biotechnology', 'forms-bridge' ),
				),
				array(
					'value' => 'Chemicals',
					'label' => __( 'Chemicals', 'forms-bridge' ),
				),
				array(
					'value' => 'Communications',
					'label' => __( 'Communications', 'forms-bridge' ),
				),
				array(
					'value' => 'Construction',
					'label' => __( 'Construction', 'forms-bridge' ),
				),
				array(
					'value' => 'Consulting',
					'label' => __( 'Consulting', 'forms-bridge' ),
				),
				array(
					'value' => 'Education',
					'label' => __( 'Education', 'forms-bridge' ),
				),
				array(
					'value' => 'Electronics',
					'label' => __( 'Electronics', 'forms-bridge' ),
				),
				array(
					'value' => 'Energy',
					'label' => __( 'Energy', 'forms-bridge' ),
				),
				array(
					'value' => 'Engineering',
					'label' => __( 'Engineering', 'forms-bridge' ),
				),
				array(
					'value' => 'Entertainment',
					'label' => __( 'Entertainment', 'forms-bridge' ),
				),
				array(
					'value' => 'Environmental',
					'label' => __( 'Environmental', 'forms-bridge' ),
				),
				array(
					'value' => 'Finance',
					'label' => __( 'Finance', 'forms-bridge' ),
				),
				array(
					'value' => 'Food & Beverage',
					'label' => __( 'Food & Beverage', 'forms-bridge' ),
				),
				array(
					'value' => 'Government',
					'label' => __( 'Government', 'forms-bridge' ),
				),
				array(
					'value' => 'Healthcare',
					'label' => __( 'Healthcare', 'forms-bridge' ),
				),
				array(
					'value' => 'Hospitality',
					'label' => __( 'Hospitality', 'forms-bridge' ),
				),
				array(
					'value' => 'Insurance',
					'label' => __( 'Insurance', 'forms-bridge' ),
				),
				array(
					'value' => 'Machinery',
					'label' => __( 'Machinery', 'forms-bridge' ),
				),
				array(
					'value' => 'Manufacturing',
					'label' => __( 'Manufacturing', 'forms-bridge' ),
				),
				array(
					'value' => 'Media',
					'label' => __( 'Media', 'forms-bridge' ),
				),
				array(
					'value' => 'Not For Profit',
					'label' => __( 'Not For Profit', 'forms-bridge' ),
				),
				array(
					'value' => 'Recreation',
					'label' => __( 'Recreation', 'forms-bridge' ),
				),
				array(
					'value' => 'Retail',
					'label' => __( 'Retail', 'forms-bridge' ),
				),
				array(
					'value' => 'Shipping',
					'label' => __( 'Shipping', 'forms-bridge' ),
				),
				array(
					'value' => 'Technology',
					'label' => __( 'Technology', 'forms-bridge' ),
				),
				array(
					'value' => 'Telecomunications',
					'label' => __( 'Telecomunications', 'forms-bridge' ),
				),
				array(
					'value' => 'Transportation',
					'label' => __( 'Transportation', 'forms-bridge' ),
				),
				array(
					'value' => 'Utilities',
					'label' => __( 'Utilities', 'forms-bridge' ),
				),
				array(
					'value' => 'Other',
					'label' => __( 'Other', 'forms-bridge' ),
				),
			),
		),
	),
	'bridge'      => array(
		'endpoint'  => 'Contacts',
		'method'    => 'create',
		'workflow'  => array( 'account', 'skip-contact' ),
		'mutations' => array(
			array(
				array(
					'from' => 'email1',
					'to'   => 'user_email',
					'cast' => 'copy',
				),
			),
			array(
				array(
					'from' => 'user_email',
					'to'   => 'email',
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
				'label'    => __( 'Company Name', 'forms-bridge' ),
				'name'     => 'accountname',
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
				'label' => __( 'Title', 'forms-bridge' ),
				'name'  => 'title',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Phone', 'forms-bridge' ),
				'name'  => 'phone',
				'type'  => 'tel',
			),
			array(
				'label' => __( 'Website', 'forms-bridge' ),
				'name'  => 'website',
				'type'  => 'url',
			),
			array(
				'label' => __( 'Address', 'forms-bridge' ),
				'name'  => 'bill_street',
				'type'  => 'text',
			),
			array(
				'label' => __( 'City', 'forms-bridge' ),
				'name'  => 'bill_city',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Postal Code', 'forms-bridge' ),
				'name'  => 'bill_code',
				'type'  => 'text',
			),
			array(
				'label' => __( 'State', 'forms-bridge' ),
				'name'  => 'bill_state',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Country', 'forms-bridge' ),
				'name'  => 'bill_country',
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
