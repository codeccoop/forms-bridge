<?php
/**
 * Vtiger Potentials (Opportunities) template.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Potentials', 'forms-bridge' ),
	'description' => __(
		'Potential (Opportunity) form template. The resulting bridge will convert form submissions into Vtiger potentials (sales opportunities).',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Potentials', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'Potentials',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'assigned_user_id',
			'label'       => __( 'Assigned User', 'forms-bridge' ),
			'description' => __(
				'User to assign the potential to',
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
			'name'        => 'related_to',
			'label'       => __( 'Related Account', 'forms-bridge' ),
			'description' => __(
				'Related account for this potential',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => 'Accounts',
				'finger'   => array(
					'value' => 'result.[].id',
					'label' => 'result.[].label',
				),
			),
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'sales_stage',
			'label'       => __( 'Sales Stage', 'forms-bridge' ),
			'description' => __(
				'Current stage in the sales process',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				array(
					'value' => 'Prospecting',
					'label' => __( 'Prospecting', 'forms-bridge' ),
				),
				array(
					'value' => 'Qualification',
					'label' => __( 'Qualification', 'forms-bridge' ),
				),
				array(
					'value' => 'Needs Analysis',
					'label' => __( 'Needs Analysis', 'forms-bridge' ),
				),
				array(
					'value' => 'Value Proposition',
					'label' => __( 'Value Proposition', 'forms-bridge' ),
				),
				array(
					'value' => 'Id. Decision Makers',
					'label' => __( 'Identifying Decision Makers', 'forms-bridge' ),
				),
				array(
					'value' => 'Perception Analysis',
					'label' => __( 'Perception Analysis', 'forms-bridge' ),
				),
				array(
					'value' => 'Proposal/Price Quote',
					'label' => __( 'Proposal/Price Quote', 'forms-bridge' ),
				),
				array(
					'value' => 'Negotiation/Review',
					'label' => __( 'Negotiation/Review', 'forms-bridge' ),
				),
				array(
					'value' => 'Closed Won',
					'label' => __( 'Closed Won', 'forms-bridge' ),
				),
				array(
					'value' => 'Closed Lost',
					'label' => __( 'Closed Lost', 'forms-bridge' ),
				),
			),
			'default'     => 'Prospecting',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'leadsource',
			'label'       => __( 'Lead Source', 'forms-bridge' ),
			'description' => __(
				'Source of the potential',
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
					'value' => 'Existing Customer',
					'label' => __( 'Existing Customer', 'forms-bridge' ),
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
					'value' => 'Partner',
					'label' => __( 'Partner', 'forms-bridge' ),
				),
				array(
					'value' => 'Self Generated',
					'label' => __( 'Self Generated', 'forms-bridge' ),
				),
				array(
					'value' => 'Other',
					'label' => __( 'Other', 'forms-bridge' ),
				),
			),
			'default'     => 'Web Site',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'opportunity_type',
			'label'       => __( 'Opportunity Type', 'forms-bridge' ),
			'description' => __(
				'Type of business opportunity',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				array(
					'value' => 'Existing Business',
					'label' => __( 'Existing Business', 'forms-bridge' ),
				),
				array(
					'value' => 'New Business',
					'label' => __( 'New Business', 'forms-bridge' ),
				),
			),
			'default'     => 'New Business',
		),
	),
	'bridge'      => array(
		'endpoint'      => 'Potentials',
		'method'        => 'create',
		'custom_fields' => array(
			array(
				'name'  => 'sales_stage',
				'value' => 'Prospecting',
			),
			array(
				'name'  => 'leadsource',
				'value' => 'Web Site',
			),
			array(
				'name'  => 'opportunity_type',
				'value' => 'New Business',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'potentialname',
					'to'   => 'potentialname',
					'cast' => 'string',
				),
				array(
					'from' => '?amount',
					'to'   => 'amount',
					'cast' => 'number',
				),
				array(
					'from' => '?closingdate',
					'to'   => 'closingdate',
					'cast' => 'string',
				),
				array(
					'from' => '?probability',
					'to'   => 'probability',
					'cast' => 'number',
				),
				array(
					'from' => '?nextstep',
					'to'   => 'nextstep',
					'cast' => 'string',
				),
				array(
					'from' => '?description',
					'to'   => 'description',
					'cast' => 'string',
				),
				array(
					'from' => '?sales_stage',
					'to'   => 'sales_stage',
					'cast' => 'string',
				),
				array(
					'from' => '?leadsource',
					'to'   => 'leadsource',
					'cast' => 'string',
				),
				array(
					'from' => '?opportunity_type',
					'to'   => 'opportunity_type',
					'cast' => 'string',
				),
				array(
					'from' => '?related_to',
					'to'   => 'related_to',
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
				'label'    => __( 'Potential Name', 'forms-bridge' ),
				'name'     => 'potentialname',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Amount', 'forms-bridge' ),
				'name'     => 'amount',
				'type'     => 'number',
				'required' => true,
			),
			array(
				'label'       => __( 'Expected Close Date', 'forms-bridge' ),
				'name'        => 'closingdate',
				'type'        => 'date',
				'required'    => true,
				'description' => __( 'Format: YYYY-MM-DD', 'forms-bridge' ),
			),
			array(
				'label'       => __( 'Probability (%)', 'forms-bridge' ),
				'name'        => 'probability',
				'type'        => 'number',
				'description' => __( 'Likelihood of closing (0-100)', 'forms-bridge' ),
			),
			array(
				'label' => __( 'Next Step', 'forms-bridge' ),
				'name'  => 'nextstep',
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
