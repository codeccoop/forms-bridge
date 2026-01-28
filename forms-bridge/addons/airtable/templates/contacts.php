<?php
/**
 * Airtable addon settings template.
 *
 * @package formsbridge
 */

return array(
	'title'       => 'Contacts',
	'description' => 'Simple contact form connected to a Airtable',
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => 'Contacts',
		),
	),
	'form'        => array(
		'title'  => 'Contacts',
		'fields' => array(
			array(
				'name'     => 'email',
				'label'    => __( 'Your email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'     => 'firstname',
				'label'    => __( 'Your first name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'     => 'lastname',
				'label'    => __( 'Your last name', 'forms-bridge' ),
				'type'     => 'text',
				'required' => true,
			),
			array(
				'name'  => 'phone',
				'label' => 'Your phone',
				'type'  => 'tel',
			),
		),
	),
	'bridge'      => array(
		'custom_fields' => array(
			array(
				'name'  => 'language',
				'value' => '$language',
			),
		),
	),
);
