<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Products by reference', 'forms-bridge' ),
	'description' => __(
		'Search for products on Dolibarr based on a list of references and returns its IDs.',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_search_products_by_ref',
	'input'       => array(
		array(
			'name'     => 'product_refs',
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
			'name'   => 'fk_products',
			'schema' => array(
				'type'            => 'array',
				'items'           => array( 'type' => 'integer' ),
				'additionalItems' => true,
			),
		),
	),
);

function forms_bridge_dolibarr_search_products_by_ref( $payload, $bridge ) {
	$sqlfilters = array();
	$refs       = (array) $payload['product_refs'];
	foreach ( $refs as $ref ) {
		$ref          = trim( $ref );
		$sqlfilters[] = "(t.ref:=:'{$ref}')";
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'dolibarr-search-products-by-ref',
				'endpoint' => '/api/index.php/products',
				'method'   => 'GET',
			)
		)
		->submit(
			array(
				'properties' => 'id,ref',
				'sqlfilters' => implode( ' or ', $sqlfilters ),
			)
		);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$fk_products = array();
	foreach ( $refs as $ref ) {
		foreach ( $response['data'] as $product ) {
			if ( $product['ref'] === $ref ) {
				$fk_products[] = $product['id'];
				break;
			}
		}
	}

	if ( count( $fk_products ) !== count( $payload['product_refs'] ) ) {
		return new WP_Error(
			'product_search_error',
			__(
				'Inconsistencies between amount of found products and search references',
				'forms-bridge'
			),
			array(
				'response'      => $response,
				'internal_refs' => $payload['product_refs'],
			)
		);
	}

	$payload['fk_products'] = $fk_products;
	return $payload;
}
