<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Sync woo products', 'forms-bridge' ),
	'description' => __(
		'Search for products from the WooCommerce order by sku on Odoo and creates new ones if someone does not exists',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_odoo_sync_products_by_ref',
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

function forms_bridge_odoo_sync_products_by_ref( $payload, $bridge ) {
	$internal_refs = array();
	foreach ( $payload['line_items'] as $line_item ) {
		if ( empty( $line_item['product']['sku'] ) ) {
			return new WP_Error(
				"SKU is required on product {$line_item['product']['name']}"
			);
		}

		$internal_refs[] = $line_item['product']['sku'];
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'odoo-search-products-by-ref',
				'endpoint' => 'product.product',
				'method'   => 'search_read',
			)
		)
		->submit(
			array( array( 'default_code', 'in', $internal_refs ) ),
			array( 'id', 'default_code' )
		);

	if ( is_wp_error( $response ) ) {
		if ( $response->get_error_code() !== 'not_found' ) {
			return $response;
		}

		$response                   = $response->get_error_data()['response'];
		$response['data']['result'] = array();
	}

	foreach ( $payload['line_items'] as $line_item ) {
		$product = null;
		foreach ( $response['data']['result'] as $candidate ) {
			if ( $candidate['default_code'] === $line_item['product']['sku'] ) {
				$product = $candidate;
				break;
			}
		}

		if ( ! $product ) {
			$product_response = $bridge
				->patch(
					array(
						'name'     => 'odoo-sync-product-by-ref',
						'endpoint' => 'product.product',
						'method'   => 'create',
					)
				)
				->submit(
					array(
						'name'         => $line_item['product']['name'],
						'list_price'   => $line_item['product']['price'],
						'default_code' => $line_item['product']['sku'],
						'sale_ok'      => true,
						'purchase_ok'  => false,
					)
				);

			if ( is_wp_error( $product_response ) ) {
				return $product_response;
			}
		}
	}

	return $payload;
}
