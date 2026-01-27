<?php
/**
 * Grist integration template
 *
 * @package formsbridge
 */

return array(
	'title'       => __( 'Grist Integration', 'forms-bridge' ),
	'description' => __( 'Template for integrating with Grist via REST API', 'forms-bridge' ),
	'icon'        => 'grist',
	'data'        => array(
		'name'      => 'grist-integration',
		'form_id'   => '',
		'backend'   => '',
		'endpoint'  => '/api/records',
		'method'    => 'POST',
		'custom_fields' => array(),
		'mutations' => array(
			array(),
		),
		'workflow'  => array(),
		'enabled'   => true,
	),
);
