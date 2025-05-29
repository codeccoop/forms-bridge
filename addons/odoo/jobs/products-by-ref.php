<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Products by reference', 'forms-bridge'),
    'description' => __(
        'Search for products on Odoo based on a list of internal references and returns its IDs.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_search_products_by_ref',
    'input' => [
        [
            'name' => 'internal_refs',
            'schema' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'additionalItems' => true,
            ],
            'required' => true,
        ],
    ],
    'output' => [
        [
            'name' => 'product_ids',
            'schema' => [
                'type' => 'array',
                'items' => ['type' => 'integer'],
                'additionalItems' => true,
            ],
        ],
    ],
];

function forms_bridge_odoo_search_products_by_ref($payload, $bridge)
{
    $response = $bridge
        ->patch([
            'name' => 'odoo-search-products-by-ref',
            'endpoint' => 'product.product',
            'method' => 'search',
        ])
        ->submit([['default_code', 'in', $payload['internal_refs']]]);

    if (is_wp_error($response)) {
        return $response;
    }

    $product_ids = $response['data']['result'];

    if (count($product_ids) !== count($payload['internal_refs'])) {
        return new WP_Error(
            'product_search_error',
            __(
                'Inconsistencies between amount of found products and search references',
                'forms-bridge'
            ),
            [
                'response' => $response,
                'internal_refs' => $payload['internal_refs'],
            ]
        );
    }

    $payload['product_ids'] = $product_ids;
    return $payload;
}
