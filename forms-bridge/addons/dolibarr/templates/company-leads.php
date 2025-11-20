<?php
/**
 * Dolibarr company leads bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Company Leads', 'forms-bridge' ),
	'description' => __(
		'Leads form template. The resulting bridge will convert form submissions into company lead projects linked to new contacts.',
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
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'typent_id',
			'label'   => __( 'Company type', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				array(
					'label' => __( 'Large company', 'forms-bridge' ),
					'value' => '2',
				),
				array(
					'label' => __( 'Medium company', 'forms-bridge' ),
					'value' => '3',
				),
				array(
					'label' => __( 'Small company', 'forms-bridge' ),
					'value' => '4',
				),
				array(
					'label' => __( 'Governmental', 'forms-bridge' ),
					'value' => '5',
				),
				array(
					'label' => __( 'Startup', 'forms-bridge' ),
					'value' => '1',
				),
				array(
					'label' => __( 'Other', 'forms-bridge' ),
					'value' => '100',
				),
			),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'stcomm_id',
			'label'   => __( 'Prospect status', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
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
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'opp_status',
			'label'   => __( 'Lead status', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
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
			'default' => __( 'Company Leads', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Company Leads', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'company_name',
				'label'    => __( 'Company name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'idprof1',
				'label'    => __( 'Tax ID', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'address',
				'label'    => __( 'Address', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'zip',
				'label'    => __( 'Postal code', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'town',
				'label'    => __( 'City', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'country',
				'label'    => __( 'Country', 'forms-bridge' ),
				'type'     => 'select',
				'options'  => array_map(
					function ( $country_code ) {
						global $forms_bridge_iso2_countries;
						return array(
							'value' => $country_code,
							'label' => $forms_bridge_iso2_countries[ $country_code ],
						);
					},
					array_keys( $forms_bridge_iso2_countries )
				),
				'required' => true,
			),
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
				'name'     => 'poste',
				'label'    => __( 'Job position', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
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
					'from' => '?userownerid',
					'to'   => 'userid',
					'cast' => 'integer',
				),
			),
			array(),
			array(
				array(
					'from' => 'company_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'name',
					'to'   => 'title',
					'cast' => 'copy',
				),
				array(
					'from' => 'email',
					'to'   => 'contact_email',
					'cast' => 'copy',
				),
			),
			array(
				array(
					'from' => 'socid',
					'to'   => 'lead_socid',
					'cast' => 'copy',
				),
				array(
					'from' => 'contact_email',
					'to'   => 'email',
					'cast' => 'string',
				),
			),
			array(
				array(
					'from' => 'lead_socid',
					'to'   => 'socid',
					'cast' => 'integer',
				),
			),
		),
		'workflow'      => array(
			'iso2-country-code',
			'country-id',
			'contact-socid',
			'contact-id',
			'next-project-ref',
		),
	),
);
