<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Products by reference', 'forms-bridge' ),
	'description' => __(
		'Search for products on Odoo based on a list of internal references and returns its IDs.',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_search_products_by_ref',
	'input'       => array(
		array(
			'name'     => 'internal_refs',
			'schema'   => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'string' ),
				'additionalItems' => true,
			),
			'required' => true,
		),
	),
	'output'      => array(
		array(
			'name'   => 'product_ids',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'integer' ),
				'additionalItems' => true,
			),
		),
	),
);

function forms_bridge_odoo_search_products_by_ref( $payload, $bridge ) {
	$response = $bridge
		->patch(
			array(
				'name'     => 'odoo-search-products-by-ref',
				'endpoint' => 'product.product',
				'method'   => 'search_read',
			)
		)
		->submit(
			array( array( 'default_code', 'in', $payload['internal_refs'] ) ),
			array( 'id', 'default_code' )
		);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$product_ids = array();
	foreach ( $payload['internal_refs'] as $ref ) {
		foreach ( $response['data']['result'] as $product ) {
			if ( $product['default_code'] === $ref ) {
				$product_ids[] = $product['id'];
				break;
			}
		}
	}

	if ( count( $product_ids ) !== count( $payload['internal_refs'] ) ) {
		return new WP_Error(
			'product_search_error',
			__(
				'Inconsistencies between amount of found products and search references',
				'forms-bridge'
			),
			array(
				'response'      => $response,
				'internal_refs' => $payload['internal_refs'],
			)
		);
	}

	$payload['product_ids'] = $product_ids;
	return $payload;
}
