<?php
/**
 * Mautic addon contacts bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contacts', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will convert form submissions into Mautic contacts and, optionally, add them to segments.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/contacts/new',
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'method',
			'value' => 'POST',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'segments',
			'label'       => __( 'Segments', 'forms-bridge' ),
			'description' => __(
				'Segments the contacts will be added to',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/api/segments',
				'finger'   => array(
					'value' => 'lists[].id',
					'label' => 'lists[].name',
				),
			),
			'is_multi'    => true,
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'tags',
			'label'       => __( 'Tags', 'forms-bridge' ),
			'description' => __(
				'Comma separated list of tags to assign to the contacts',
				'forms-bridge'
			),
			'type'        => 'text',
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Contacts', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Contacts', 'forms-bridge' ),
		'fields' => array(
			array(
				'name'     => 'your-email',
				'label'    => __( 'Your email', 'forms-bridge' ),
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'  => 'your-first-name',
				'label' => __( 'Your first name', 'forms-bridge' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'your-last-name',
				'label' => __( 'Your last name', 'forms-bridge' ),
				'type'  => 'text',
			),
		),
	),
	'bridge'      => array(
		'method'    => 'POST',
		'endpoint'  => '/api/contacts/new',
		'mutations' => array(
			array(
				array(
					'from' => 'your-email',
					'to'   => 'email',
					'cast' => 'string',
				),
				array(
					'from' => '?your-first-name',
					'to'   => 'firstname',
					'cast' => 'string',
				),
				array(
					'from' => '?your-last-name',
					'to'   => 'lastname',
					'cast' => 'string',
				),
			),
		),
	),
);
