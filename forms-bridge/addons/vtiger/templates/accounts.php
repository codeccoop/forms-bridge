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
		'Account form template. The resulting bridge will convert form submissions into Vtiger accounts (organizations).',
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
			'value' => 'Accounts',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'assigned_user_id',
			'label'       => __( 'Assigned User', 'forms-bridge' ),
			'description' => __(
				'User to assign the account to',
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
			'name'        => 'accounttype',
			'label'       => __( 'Account Type', 'forms-bridge' ),
			'description' => __(
				'Type of account',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
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
			'default'     => 'Prospect',
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
					'value' => 'Finance',
					'label' => __( 'Finance', 'forms-bridge' ),
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
					'value' => 'Manufacturing',
					'label' => __( 'Manufacturing', 'forms-bridge' ),
				),
				array(
					'value' => 'Media',
					'label' => __( 'Media', 'forms-bridge' ),
				),
				array(
					'value' => 'Retail',
					'label' => __( 'Retail', 'forms-bridge' ),
				),
				array(
					'value' => 'Technology',
					'label' => __( 'Technology', 'forms-bridge' ),
				),
				array(
					'value' => 'Transportation',
					'label' => __( 'Transportation', 'forms-bridge' ),
				),
				array(
					'value' => 'Other',
					'label' => __( 'Other', 'forms-bridge' ),
				),
			),
		),
	),
	'bridge'      => array(
		'endpoint'      => 'Accounts',
		'method'        => 'create',
		'custom_fields' => array(
			array(
				'name'  => 'accounttype',
				'value' => 'Prospect',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'accountname',
					'to'   => 'accountname',
					'cast' => 'string',
				),
				array(
					'from' => '?email',
					'to'   => 'email1',
					'cast' => 'string',
				),
				array(
					'from' => '?phone',
					'to'   => 'phone',
					'cast' => 'string',
				),
				array(
					'from' => '?fax',
					'to'   => 'fax',
					'cast' => 'string',
				),
				array(
					'from' => '?website',
					'to'   => 'website',
					'cast' => 'string',
				),
				array(
					'from' => '?description',
					'to'   => 'description',
					'cast' => 'string',
				),
				array(
					'from' => '?employees',
					'to'   => 'employees',
					'cast' => 'integer',
				),
				array(
					'from' => '?annual_revenue',
					'to'   => 'annual_revenue',
					'cast' => 'number',
				),
				array(
					'from' => '?address',
					'to'   => 'bill_street',
					'cast' => 'string',
				),
				array(
					'from' => '?city',
					'to'   => 'bill_city',
					'cast' => 'string',
				),
				array(
					'from' => '?state',
					'to'   => 'bill_state',
					'cast' => 'string',
				),
				array(
					'from' => '?postal_code',
					'to'   => 'bill_code',
					'cast' => 'string',
				),
				array(
					'from' => '?country',
					'to'   => 'bill_country',
					'cast' => 'string',
				),
				array(
					'from' => '?accounttype',
					'to'   => 'accounttype',
					'cast' => 'string',
				),
				array(
					'from' => '?industry',
					'to'   => 'industry',
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
				'label'    => __( 'Account Name', 'forms-bridge' ),
				'name'     => 'accountname',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label' => __( 'Email', 'forms-bridge' ),
				'name'  => 'email',
				'type'  => 'email',
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
				'name'  => 'address',
				'type'  => 'text',
			),
			array(
				'label' => __( 'City', 'forms-bridge' ),
				'name'  => 'city',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Country', 'forms-bridge' ),
				'name'  => 'country',
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
