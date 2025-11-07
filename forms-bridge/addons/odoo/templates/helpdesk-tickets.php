<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Helpdesk ticket', 'forms-bridge' ),
	'description' => __(
		'Convert form submissions to helpdesk tickets',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Helpdesk', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'helpdesk.ticket',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'team_id',
			'label'    => __( 'Owner team', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				'endpoint' => 'helpdesk.ticket.team',
				'finger'   => array(
					'value' => 'result.[].id',
					'label' => 'result.[].name',
				),
			),
			'required' => true,
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'channel_id',
			'label'   => __( 'Incoming channel', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				'endpoint' => 'helpdesk.ticket.channel',
				'finger'   => array(
					'value' => 'result.[].id',
					'label' => 'result.[].name',
				),
			),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'user_id',
			'label'   => __( 'Owner user', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				'endpoint' => 'res.user',
				'finger'   => array(
					'value' => 'result.[].id',
					'label' => 'result.[].name',
				),
			),
		),
	),
	'bridge'      => array(
		'endpoint'  => 'helpdesk.ticket',
		'mutations' => array(
			array(
				array(
					'from' => 'your-name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'name',
					'to'   => 'partner_name',
					'cast' => 'copy',
				),
				array(
					'from' => 'your-email',
					'to'   => 'email',
					'cast' => 'string',
				),
				array(
					'from' => 'email',
					'to'   => 'partner_email',
					'cast' => 'copy',
				),
			),
			array(
				array(
					'from' => 'ticket-name',
					'to'   => 'name',
					'cast' => 'string',
				),
			),
		),
		'workflow'  => array( 'contact' ),
	),
	'form'        => array(
		'title'  => __( 'Helpdesk', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'your-name',
				'label'    => __( 'Your name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'your-email',
				'label'    => __( 'Your email', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'ticket-name',
				'label'    => __( 'Subject', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'description',
				'label'    => __( 'Message', 'forms-bridge' ),
				'type'     => 'textarea',
				'required' => true,
			),
		),
	),
);
