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
		'Contact form template. The resulting bridge will send form submissions as direct messages on Slack',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/chat.postMessage',
		),
		array(
			'ref'     => '#bridge/custom_fields[]',
			'name'    => 'channel',
			'label'   => __( 'User', 'forms-bridge' ),
			'type'    => 'select',
			'options' => array(
				'endpoint' => '/api/users.list',
				'finger'   => array(
					'value' => 'members[].id',
					'label' => 'members[].name',
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
		'endpoint'  => '/api/chat.postMessage',
		'mutations' => array(
			array(
				array(
					'from' => 'your-name',
					'to'   => 'text.name',
					'cast' => 'string',
				),
				array(
					'from' => 'your-email',
					'to'   => 'text.email',
					'cast' => 'string',
				),
				array(
					'from' => '?comments',
					'to'   => 'text.comments',
					'cast' => 'string',
				),
				array(
					'from' => 'text',
					'to'   => 'text',
					'cast' => 'pretty_json',
				),
			),
		),
	),
);
