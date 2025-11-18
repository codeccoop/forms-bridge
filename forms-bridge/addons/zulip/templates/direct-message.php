<?php
/**
 * Zulip addon direct message bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Direct Messages', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will send form submissions as direct messages on Zulip',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/v1/messages',
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'to[0]',
			'label'   => __( 'User', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				'endpoint' => '/api/v1/users',
				'finger'   => array(
					'value' => 'members[].user_id',
					'label' => 'members[].delivery_email',
				),
			),
		),
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Direct Messages', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Direct Messages', 'forms-bridge' ),
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
				'type'     => 'email',
				'required' => true,
			),
			array(
				'name'  => 'comments',
				'label' => __( 'Comments', 'forms-bridge' ),
				'type'  => 'textarea',
			),
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/v1/messages',
		'custom_fields' => array(
			array(
				'name'  => 'type',
				'value' => 'direct',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'to[]',
					'to'   => 'to[]',
					'cast' => 'integer',
				),
				array(
					'from' => 'to',
					'to'   => 'to',
					'cast' => 'json',
				),
				array(
					'from' => 'your-name',
					'to'   => 'content.name',
					'cast' => 'string',
				),
				array(
					'from' => 'your-email',
					'to'   => 'content.email',
					'cast' => 'string',
				),
				array(
					'from' => '?comments',
					'to'   => 'content.comments',
					'cast' => 'string',
				),
				array(
					'from' => 'content',
					'to'   => 'content',
					'cast' => 'pretty_json',
				),
			),
		),
	),
);
