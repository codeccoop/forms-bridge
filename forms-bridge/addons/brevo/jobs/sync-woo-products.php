<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Sync woo products', 'forms-bridge' ),
	'description' => __(
		'Synchronize WooCommerce orders products with the eCommerce module of Brevo',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_brevo_sync_woo_products',
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
							'required'   => array( 'id', 'name', 'price' ),
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

function forms_bridge_brevo_sync_woo_products( $payload, $bridge ) {
	$response = $bridge
		->patch(
			array(
				'name'     => 'brevo-search-products',
				'endpoint' => '/v3/products',
				'method'   => 'GET',
			)
		)
		->submit(
			array(
				'offset' => 0,
				'order'  => 'desc',
			)
		);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	foreach ( $payload['line_items'] as $line_item ) {
		$product = null;
		foreach ( $response['data']['products'] as $candidate ) {
			if ( $candidate['id'] == $line_item['product_id'] ) {
				$product = $candidate;
				break;
			}
		}

		if ( ! $product ) {
			$product = array(
				'updateEnabled' => false,
				'id'            => (string) $line_item['product_id'],
				'name'          => $line_item['product']['name'],
				'price'         => $line_item['product']['price'],
				'stock'         => $line_item['product']['stock_quantity'],
			);

			if ( ! empty( $line_item['product']['parent_id'] ) ) {
				$product['parent_id'] = $line_item['product']['parent_id'];
			}

			if ( ! empty( $line_item['product']['sku'] ) ) {
				$product['sku'] = $line_item['product']['sku'];
			}

			$product_response = $bridge
				->patch(
					array(
						'name'     => 'brevo-sync-woo-product',
						'method'   => 'POST',
						'endpoint' => '/v3/products',
					)
				)
				->submit( $product );

			if ( is_wp_error( $product_response ) ) {
				return $product_response;
			}
		}
	}

	return $payload;
}
