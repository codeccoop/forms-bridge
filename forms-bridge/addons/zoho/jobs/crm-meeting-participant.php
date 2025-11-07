<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_crm_meeting_participant( $payload, $bridge ) {
	$lead = forms_bridge_zoho_crm_create_lead( $payload, $bridge );

	if ( is_wp_error( $lead ) ) {
		return $lead;
	}

	$payload['Participants'][] = array(
		'type'        => 'lead',
		'participant' => $lead['id'],
	);

	return $payload;
}

return array(
	'title'       => __( 'CRM meeting participant', 'forms-bridge' ),
	'description' => __(
		'Search for a lead or creates a new one and sets its ID as meeting participant',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_crm_meeting_participant',
	'input'       => array(
		array(
			'name'     => 'Last_Name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'First_Name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Full_Name',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Designation',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Email',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Secondary_Email',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Mobile',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Fax',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Website',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Lead_Source',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Lead_Status',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Description',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Company',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'No_of_Employees',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Industry',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Annual_Revenue',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Street',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'City',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'State',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Zip_Code',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'Tag',
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'properties'           => array(
						'name' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
					'required'             => array( 'name' ),
				),
			),
		),
	),
	'output'      => array(
		array(
			'name'   => 'Participants',
			'schema' => array(
				'type'            => 'array',
				'items'           => array(
					'type'       => 'object',
					'properties' => array(
						'type'        => array( 'type' => 'string' ),
						'participant' => array( 'type' => 'string' ),
					),
				),
				'additionalItems' => true,
			),
		),
	),
);
