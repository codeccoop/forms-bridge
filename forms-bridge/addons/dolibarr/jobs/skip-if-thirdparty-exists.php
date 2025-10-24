<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_dolibarr_skip_thirdparty( $payload, $bridge ) {
	$thirdparty = forms_bridge_dolibarr_search_thirdparty( $payload, $bridge );

	if ( is_wp_error( $thirdparty ) ) {
		return $thirdparty;
	}

	if ( isset( $thirdparty['id'] ) ) {
		$patch       = $payload;
		$patch['id'] = $thirdparty['id'];

		if ( isset( $thirdparty['code_client'] ) ) {
			$patch['code_client'] = $thirdparty['code_client'];
		}

		$response = forms_bridge_dolibarr_update_thirdparty( $patch, $bridge );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return;
	}

	return $payload;
}

return array(
	'title'       => __( 'Skip if thirdparty exists', 'forms-bridge' ),
	'description' => __(
		'Aborts form submission if a thirdparty already exists',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_skip_thirdparty',
	'input'       => array(
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'email',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'idprof1',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'     => 'name',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'name' ),
		),
		array(
			'name'     => 'email',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'email' ),
		),
		array(
			'name'     => 'idprof1',
			'schema'   => array( 'type' => 'string' ),
			'requires' => array( 'idprof1' ),
		),
	),
);
