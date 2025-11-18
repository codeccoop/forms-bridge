<?php
/**
 * Zulip addon contact stream bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Contacts Stream', 'forms-bridge' ),
	'description' => __(
		'Contact form template. The resulting bridge will notify form submissions in a Zulip stream',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/v1/messages',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'to[0]',
			'label'       => __( 'Stream', 'forms-bridge' ),
			'description' => __(
				'Name of the stream (channel) where notifications will be sent',
				'forms-bridge'
			),
			'type'        => 'select',
			'options'     => array(
				'endpoint' => '/api/v1/streams',
				'finger'   => array(
					'value' => 'streams[].stream_id',
					'label' => 'streams[].name',
				),
			),
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'topic',
			'label'       => __( 'Topic', 'forms-bridge' ),
			'description' => __( 'Topic under which the messages will be notified', 'forms-bridge' ),
			'type'        => 'text',
			'default'     => 'WordPress Contacts',
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
		'endpoint'      => '/api/v1/messages',
		'custom_fields' => array(
			array(
				'name'  => 'type',
				'value' => 'stream',
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
