<?php

return array(
	'title'       => __( 'Job position', 'forms-bridge' ),
	'description' => __(
		'Job application form. The resulting bridge will convert form submissions into applications to a job from the Human Resources module',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Job position', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => 'hr.applicant',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'user_id',
			'label'       => __( 'Owner', 'forms-bridge' ),
			'description' => __(
				'Name of the owner user of the application',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => 'res.users',
				'finger'   => array(
					'value' => 'result.[].id',
					'label' => 'result.[].name',
				),
			),
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'job_id',
			'label'   => __( 'Job position', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				'endpoint' => 'hr.job',
				'finger'   => array(
					'value' => 'result.[].id',
					'label' => 'result.[].name',
				),
			),
		),
	),
	'bridge'      => array(
		'endpoint'  => 'hr.applicant',
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
					'to'   => 'email_from',
					'cast' => 'copy',
				),
				array(
					'from' => 'your-phone',
					'to'   => 'phone',
					'cast' => 'string',
				),
				array(
					'from' => 'phone',
					'to'   => 'partner_phone',
					'cast' => 'copy',
				),
				array(
					'from' => 'user_id',
					'to'   => 'user_id',
					'cast' => 'integer',
				),
				array(
					'from' => 'job_id',
					'to'   => 'job_id',
					'cast' => 'integer',
				),
			),
			array(),
			array(),
			array(
				array(
					'from' => 'curriculum',
					'to'   => 'curriculum',
					'cast' => 'null',
				),
				array(
					'from' => 'curriculum_filename',
					'to'   => 'curriculum_filename',
					'cast' => 'null',
				),
			),
		),
		'workflow'  => array( 'contact', 'candidate', 'attachments' ),
	),
	'form'        => array(
		'title'  => 'Job position',
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
				'name'     => 'your-phone',
				'label'    => __( 'Your phone', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'applicant_notes',
				'label'    => __( 'Description', 'forms-bridge' ),
				'type'     => 'textarea',
				'required' => true,
			),
			array(
				'name'     => 'curriculum',
				'label'    => __( 'Curriculum', 'forms-brirdge' ),
				'type'     => 'file',
				'required' => true,
			),
		),
	),
);
