<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Sync woo products', 'forms-bridge' ),
	'description' => __(
		'Search for products from the WooCommerce order by sku on Holded and creates new ones if someone does not exists',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_holded_sync_products_by_sku',
	'input'       => array(
		array(
			'name'     => 'line_items',
			'schema'   => array(
				'type'            => 'array',
				'items'           => array(
					'type'                 => 'object',
					'properties'           => array(
						'product_id' => array( 'type' => 'integer' ),
						'product'    => array(
							'type'       => 'object',
							'properties' => array(
								'id'             => array( 'type' => 'integer' ),
								'parent_id'      => array( 'type' => 'integer' ),
								'sku'            => array( 'type' => 'string' ),
								'name'           => array( 'type' => 'string' ),
								'slug'           => array( 'type' => 'string' ),
								'price'          => array( 'type' => 'number' ),
								'sale_price'     => array( 'type' => 'number' ),
								'regular_price'  => array( 'type' => 'number' ),
								'stock_quantity' => array( 'type' => 'number' ),
								'stock_status'   => array( 'type' => 'string' ),
							),
							'required'   => array( 'sku', 'name', 'price' ),
						),
					),
					'additionalProperties' => true,
				),
				'additionalItems' => true,
			),
			'required' => true,
		),
	),
	'output'      => array(
		array(
			'name'   => 'line_items',
			'schema' => array(
				'type'            => 'array',
				'items'           => array(
					'type'                 => 'object',
					'properties'           => array(
						'product_id' => array( 'type' => 'integer' ),
						'product'    => array(
							'type'       => 'object',
							'properties' => array(
								'id'             => array( 'type' => 'integer' ),
								'parent_id'      => array( 'type' => 'integer' ),
								'sku'            => array( 'type' => 'string' ),
								'name'           => array( 'type' => 'string' ),
								'slug'           => array( 'type' => 'string' ),
								'price'          => array( 'type' => 'number' ),
								'sale_price'     => array( 'type' => 'number' ),
								'regular_price'  => array( 'type' => 'number' ),
								'stock_quantity' => array( 'type' => 'number' ),
								'stock_status'   => array( 'type' => 'string' ),
							),
						),
					),
					'additionalProperties' => true,
				),
				'additionalItems' => true,
			),
		),
	),
);

function forms_bridge_holded_sync_products_by_sku( $payload, $bridge ) {
	$product_refs = array();
	foreach ( $payload['line_items'] as $line_item ) {
		if ( empty( $line_item['product']['sku'] ) ) {
			return new WP_Error(
				"SKU is required on product {$line_item['product']['name']}"
			);
		}

		$product_refs[] = $line_item['product']['sku'];
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'holded-search-products',
				'endpoint' => '/api/invoicing/v1/products',
				'method'   => 'GET',
			)
		)
		->submit();

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	foreach ( $payload['line_items'] as $line_item ) {
		$product = null;
		foreach ( $response['data'] as $candidate ) {
			if ( $candidate['sku'] === $line_item['product']['sku'] ) {
				$product = $candidate;
				break;
			}
		}

		if ( ! $product ) {
			$product_response = $bridge
				->patch(
					array(
						'name'     => 'holded-sync-product-by-sku',
						'endpoint' => '/api/invoicing/v1/products',
						'method'   => 'POST',
					)
				)
				->submit(
					array(
						'kind'  => 'simple',
						'name'  => $line_item['product']['name'],
						'price' => $line_item['product']['price'],
						'sku'   => $line_item['product']['sku'],
					)
				);

			if ( is_wp_error( $product_response ) ) {
				return $product_response;
			}
		}
	}
	return $payload;
}
