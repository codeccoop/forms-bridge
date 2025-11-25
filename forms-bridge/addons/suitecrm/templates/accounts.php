<?php
/**
 * SuiteCRM Accounts template.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Accounts', 'forms-bridge' ),
	'description' => __(
		'Account bridge template. The resulting bridge will convert form submissions into SuiteCRM accounts (companies/organizations).',
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
					'value' => 'entry_list[].id',
					'label' => 'entry_list[].name_value_list.name.value',
				),
			),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'account_type',
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
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'industry',
			'label'   => __( 'Industry', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
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
			'default' => 'Web Site',
		),
	),
	'bridge'      => array(
		'endpoint' => 'Contacts',
		'method'   => 'set_entry',
		'workflow' => array( 'account', 'skip-contact' ),
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
				'label'    => __( 'Company Name', 'forms-bridge' ),
				'name'     => 'name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label' => __( 'Email', 'forms-bridge' ),
				'name'  => 'email1',
				'type'  => 'email',
			),
			array(
				'label' => __( 'Phone', 'forms-bridge' ),
				'name'  => 'phone_office',
				'type'  => 'tel',
			),
			array(
				'label' => __( 'Website', 'forms-bridge' ),
				'name'  => 'website',
				'type'  => 'url',
			),
			array(
				'label' => __( 'Address', 'forms-bridge' ),
				'name'  => 'billing_address_street',
				'type'  => 'text',
			),
			array(
				'label' => __( 'City', 'forms-bridge' ),
				'name'  => 'billing_address_city',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Postal Code', 'forms-bridge' ),
				'name'  => 'billing_address_postalcode',
				'type'  => 'text',
			),
			array(
				'label' => __( 'State', 'forms-bridge' ),
				'name'  => 'billing_address_state',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Country', 'forms-bridge' ),
				'name'  => 'billing_address_country',
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
