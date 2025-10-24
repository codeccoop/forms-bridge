<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Shipping address', 'forms-bridge' ),
	'description' => __(
		'Creates a shipping address linked to a contact.',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_shipping_address',
	'input'       => array(
		array(
			'name'     => 'partner_id',
			'schema'   => array( 'type' => 'integer' ),
			'required' => true,
		),
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
			'name'   => 'phone',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'mobile',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'street',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'street2',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'city',
			'schema' => array( 'type' => 'string' ),
		),
		array(
			'name'   => 'zip',
			'schema' => array( 'type' => 'string' ),
		),
		// [
		// 'name' => 'state',
		// 'schema' => ['type' => 'string'],
		// ],
		// [
		// 'name' => 'country',
		// 'schema' => ['type' => 'string'],
		// ],
		array(
			'name'   => 'comment',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(),
);

function forms_bridge_odoo_shipping_address( $payload, $bridge ) {
	$query = array(
		array( 'type', '=', 'delivery' ),
		array( 'parent_id', '=', $payload['partner_id'] ),
		array( 'name', '=', $payload['name'] ),
	);

	$response = $bridge
		->patch(
			array(
				'name'     => 'odoo-search-address',
				'method'   => 'search',
				'endpoint' => 'res.partner',
			)
		)
		->submit( $query );

	if ( ! is_wp_error( $response ) ) {
		return $payload;
	}

	$address = array();
	foreach ( $query as $filter ) {
		$address[ $filter[0] ] = $filter[2];
	}

	$address_fields = array(
		'email',
		'phone',
		'mobile',
		'street',
		'street2',
		'city',
		'zip',
		// 'state',
		// 'country',
		'comment',
	);

	foreach ( $address_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$address[ $field ] = $payload[ $field ];
		}
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'odoo-delivery-address',
				'endpoint' => 'res.partner',
				'method'   => 'create',
			)
		)
		->submit( $address );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	return $payload;
}
