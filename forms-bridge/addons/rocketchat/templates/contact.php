<?php
/**
 * Rocket.Chat addon contact channel bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contacts Channel', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will notify form submissions in a Slack channel',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/v1/chat.postMessage',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'roomId',
			'label'       => __( 'Channel', 'forms-bridge' ),
			'description' => __(
				'Name of the channel where messages will be sent',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/api/v1/rooms.get',
				'finger'   => array(
					'value' => 'update[]._id',
					'label' => 'update[].name',
				),
			),
			'required'    => true,
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
		'endpoint'  => '/api/v1/chat.postMessage',
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
