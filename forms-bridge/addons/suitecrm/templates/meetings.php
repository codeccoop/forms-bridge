<?php
/**
 * SuiteCRM meetings bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Meetings', 'forms-bridge' ),
	'description' => __(
		'Meetings bridge template. The resulting bridge will convert form submissions into SuiteCRM meetings.',
		'forms-bridge',
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Meetings', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'Meetings',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'duration_hours',
			'label'    => __( 'Duration (Hours)', 'forms-bridge' ),
			'type'     => 'number',
			'default'  => 1,
			'required' => true,
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'duration_minutes',
			'label'    => __( 'Duration (Minutes)', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				array(
					'value' => '0',
					'label' => '.00',
				),
				array(
					'value' => '15',
					'label' => '.15',
				),
				array(
					'value' => '30',
					'label' => '.30',
				),
				array(
					'value' => '45',
					'label' => '.45',
				),
			),
			'default'  => '00',
			'required' => true,
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'meeting_assigned_user_id',
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
			'required'    => true,
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
		'endpoint'      => 'Meetings',
		'method'        => 'set_entry',
		'custom_fields' => array(
			array(
				'name'  => 'meeting_status',
				'value' => 'Planned',
			),
			array(
				'name'  => 'meeting_type',
				'value' => 'Sugar',
			),
			array(
				'name'  => 'parent_type',
				'value' => 'Contacts',
			),
		),
		'workflow'      => array( 'date-fields-to-date', 'contact', 'meeting-invitees' ),
		'mutations'     => array(
			array(),
			array(
				array(
					'from' => 'first_name',
					'to'   => 'meeting_name[0]',
					'cast' => 'copy',
				),
				array(
					'from' => 'last_name',
					'to'   => 'meeting_name[1]',
					'cast' => 'copy',
				),
				array(
					'from' => 'meeting_name',
					'to'   => 'meeting_name',
					'cast' => 'concat',
				),
			),
			array(
				array(
					'from' => 'contact_id',
					'to'   => 'parent_id',
					'cast' => 'copy',
				),
				array(
					'from' => 'meeting_assigned_user_id',
					'to'   => 'assigned_user_id',
					'cast' => 'string',
				),
				array(
					'from' => 'meeting_type',
					'to'   => 'type',
					'cast' => 'string',
				),
				array(
					'from' => 'meeting_status',
					'to'   => 'status',
					'cast' => 'string',
				),
				array(
					'from' => 'meeting_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'meeting_description',
					'to'   => 'description',
					'cast' => 'string',
				),
				array(
					'from' => 'datetime',
					'to'   => 'date_start',
					'cast' => 'string',
				),
				array(
					'from' => 'duration_hours',
					'to'   => 'duration_hours',
					'cast' => 'integer',
				),
				array(
					'from' => 'duration_minutes',
					'to'   => 'duration_minutes',
					'cast' => 'integer',
				),
			),
		),
	),
	'form'        => array(
		'title'  => __( 'Meetings', 'forms-bridge' ),
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
				'label' => __( 'Mobile', 'forms-bridge' ),
				'name'  => 'phone_mobile',
				'type'  => 'tel',
			),
			array(
				'name'     => 'date',
				'label'    => __( 'Date', 'forms-bridge' ),
				'type'     => 'date',
				'required' => true,
			),
			array(
				'name'     => 'hour',
				'label'    => __( 'Hour', 'forms-bridge' ),
				'type'     => 'select',
				'required' => true,
				'options'  => array(
					array(
						'label' => __( '1 AM', 'forms-bridge' ),
						'value' => '01',
					),
					array(
						'label' => __( '2 AM', 'forms-bridge' ),
						'value' => '02',
					),
					array(
						'label' => __( '3 AM', 'forms-bridge' ),
						'value' => '03',
					),
					array(
						'label' => __( '4 AM', 'forms-bridge' ),
						'value' => '04',
					),
					array(
						'label' => __( '5 AM', 'forms-bridge' ),
						'value' => '05',
					),
					array(
						'label' => __( '6 AM', 'forms-bridge' ),
						'value' => '06',
					),
					array(
						'label' => __( '7 AM', 'forms-bridge' ),
						'value' => '07',
					),
					array(
						'label' => __( '8 AM', 'forms-bridge' ),
						'value' => '08',
					),
					array(
						'label' => __( '9 AM', 'forms-bridge' ),
						'value' => '09',
					),
					array(
						'label' => __( '10 AM', 'forms-bridge' ),
						'value' => '10',
					),
					array(
						'label' => __( '11 AM', 'forms-bridge' ),
						'value' => '11',
					),
					array(
						'label' => __( '12 AM', 'forms-bridge' ),
						'value' => '12',
					),
					array(
						'label' => __( '1 PM', 'forms-bridge' ),
						'value' => '13',
					),
					array(
						'label' => __( '2 PM', 'forms-bridge' ),
						'value' => '14',
					),
					array(
						'label' => __( '3 PM', 'forms-bridge' ),
						'value' => '15',
					),
					array(
						'label' => __( '4 PM', 'forms-bridge' ),
						'value' => '16',
					),
					array(
						'label' => __( '5 PM', 'forms-bridge' ),
						'value' => '17',
					),
					array(
						'label' => __( '6 PM', 'forms-bridge' ),
						'value' => '18',
					),
					array(
						'label' => __( '7 PM', 'forms-bridge' ),
						'value' => '19',
					),
					array(
						'label' => __( '8 PM', 'forms-bridge' ),
						'value' => '20',
					),
					array(
						'label' => __( '9 PM', 'forms-bridge' ),
						'value' => '21',
					),
					array(
						'label' => __( '10 PM', 'forms-bridge' ),
						'value' => '22',
					),
					array(
						'label' => __( '11 PM', 'forms-bridge' ),
						'value' => '23',
					),
					array(
						'label' => __( '12 PM', 'forms-bridge' ),
						'value' => '24',
					),
				),
			),
			array(
				'name'     => 'minute',
				'label'    => __( 'Minute', 'forms-bridge' ),
				'type'     => 'select',
				'required' => true,
				'options'  => array(
					array(
						'label' => '00',
						'value' => '00.0',
					),
					array(
						'label' => '05',
						'value' => '05',
					),
					array(
						'label' => '10',
						'value' => '10',
					),
					array(
						'label' => '15',
						'value' => '15',
					),
					array(
						'label' => '20',
						'value' => '20',
					),
					array(
						'label' => '25',
						'value' => '25',
					),
					array(
						'label' => '30',
						'value' => '30',
					),
					array(
						'label' => '35',
						'value' => '35',
					),
					array(
						'label' => '40',
						'value' => '40',
					),
					array(
						'label' => '45',
						'value' => '45',
					),
					array(
						'label' => '50',
						'value' => '50',
					),
					array(
						'label' => '55',
						'value' => '55',
					),
				),
			),
			array(
				'name'  => 'meeting_description',
				'type'  => 'textarea',
				'label' => __( 'Comments', 'forms-bridge' ),
			),
		),
	),
);
