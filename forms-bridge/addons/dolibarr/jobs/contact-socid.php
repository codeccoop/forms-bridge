<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_dolibarr_thirdparty_socid( $payload, $bridge ) {
	$thirdparty = forms_bridge_dolibarr_create_thirdparty( $payload, $bridge );

	if ( is_wp_error( $thirdparty ) ) {
		return $thirdparty;
	}

	$payload['socid'] = (int) $thirdparty['id'];
	return $payload;
}

return array(
	'title'       => __( 'Third party', 'forms-bridge' ),
	'description' => __(
		'Creates a new third party and returns its ID as the socid of the payload.',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_thirdparty_socid',
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
			'name'   => 'code_client',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'idprof1',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'idprof2',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'tva_intra',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'fax',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'url',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'address',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'zip',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'town',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'country_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'region_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'state_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'typent_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'status',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'client',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'fournisseur',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'stcomm_id',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'note_public',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'no_email',
			'schema' => array( 'type' => 'integer' ),
		),
		array(
			'name'   => 'parent',
			'schema' => array( 'type' => 'integer' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'socid',
			'schema' => array( 'type' => 'string' ),
		),
	),
);
