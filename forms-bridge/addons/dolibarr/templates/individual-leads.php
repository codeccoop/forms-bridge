<?php
/**
 * Dolibarr individual leads bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Leads', 'forms-bridge' ),
	'description' => __(
		'Lead form template. The resulting bridge will convert form submissions into lead projects linked to new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/index.php/projects',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'userownerid',
			'label'       => __( 'Owner', 'forms-bridge' ),
			'description' => __( 'Owner user of the lead', 'forms-bridge' ),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/api/index.php/users',
				'finger'   => array(
					'value' => '[].id',
					'label' => '[].login',
				),
			),
			'required'    => true,
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'stcomm_id',
			'label'    => __( 'Prospect status', 'forms-bridge' ),
			'required' => true,
			'type'     => 'select',
			'options'  => array(
				array(
					'label' => __( 'Never contacted', 'forms-bridge' ),
					'value' => '0',
				),
				array(
					'label' => __( 'To contact', 'forms-bridge' ),
					'value' => '1',
				),
				array(
					'label' => __( 'Contact in progress', 'forms-bridge' ),
					'value' => '2',
				),
				array(
					'label' => __( 'Contacted', 'forms-bridge' ),
					'value' => '3',
				),
				array(
					'label' => __( 'Do not contact', 'forms-bridge' ),
					'value' => '-1',
				),
			),
			'default'  => '0',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'opp_status',
			'label'    => __( 'Lead status', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				array(
					'label' => __( 'Prospection', 'forms-bridge' ),
					'value' => '1',
				),
				array(
					'label' => __( 'Qualification', 'forms-bridge' ),
					'value' => '2',
				),
				array(
					'label' => __( 'Proposal', 'forms-bridge' ),
					'value' => '3',
				),
				array(
					'label' => __( 'Negociation', 'forms-bridge' ),
					'value' => '4',
				),
			),
			'required' => true,
			'default'  => '1',
		),
		array(
			'ref'   => '#bridge/custom_fields[]',
			'name'  => 'opp_amount',
			'label' => __( 'Lead amount', 'forms-bridge' ),
			'type'  => 'number',
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Leads', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Leads', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'firstname',
				'label'    => __( 'First name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'lastname',
				'label'    => __( 'Last name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'email',
				'label'    => __( 'Email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'  => 'phone',
				'label' => __( 'Phone', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'note_private',
				'label' => __( 'Comments', 'forms-bridge' ),
				'type'  => 'textarea',
			),
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/index.php/projects',
		'custom_fields' => array(
			array(
				'name'  => 'status',
				'value' => '1',
			),
			array(
				'name'  => 'typent_id',
				'value' => '8',
			),
			array(
				'name'  => 'client',
				'value' => '2',
			),
			array(
				'name'  => 'usage_opportunity',
				'value' => '1',
			),
			array(
				'name'  => 'date_start',
				'value' => '$timestamp',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'firstname',
					'to'   => 'name[0]',
					'cast' => 'string',
				),
				array(
					'from' => 'lastname',
					'to'   => 'name[1]',
					'cast' => 'string',
				),
				array(
					'from' => 'name',
					'to'   => 'name',
					'cast' => 'concat',
				),
				array(
					'from' => 'name',
					'to'   => 'title',
					'cast' => 'copy',
				),
				array(
					'from' => 'userownerid',
					'to'   => 'userid',
					'cast' => 'integer',
				),
			),
		),
		'workflow'      => array( 'contact-socid', 'next-project-ref' ),
	),
);
