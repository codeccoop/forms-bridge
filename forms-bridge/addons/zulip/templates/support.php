<?php
/**
 * Zulip addon support stream bridge template
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Support Stream', 'forms-bridge' ),
	'description' => __(
		'Support form template. The resulting bridge will notify form submissions in a Zulip stream',
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
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Support', 'forms-bridge' ),
		),
	),
	'form'        => array(
		'title'  => __( 'Support', 'forms-bridge' ),
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
				'name'     => 'topic',
				'label'    => __( 'Topic', 'forms-bridge' ),
				'type'     => 'select',
				'options'  => array(
					array(
						'value' => 'A',
						'label' => 'Option 1',
					),
					array(
						'value' => 'B',
						'label' => 'Option 2',
					),
				),
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
