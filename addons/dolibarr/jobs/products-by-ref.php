<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Products by reference', 'forms-bridge'),
    'description' => __(
        'Search for products on Dolibarr based on a list of references and returns its IDs.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_search_products_by_ref',
    'input' => [
        [
            'name' => 'product_refs',
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
            'name' => 'fk_products',
            'schema' => [
                'type' => 'array',
                'items' => ['type' => 'integer'],
                'additionalItems' => true,
            ],
        ],
    ],
];

function forms_bridge_dolibarr_search_products_by_ref($payload, $bridge)
{
    $refs = implode(',', array_map('trim', $payload['product_refs']));
    $response = $bridge
        ->patch([
            'name' => 'dolibarr-search-products-by-ref',
            'endpoint' => '/api/index.php/products',
            'method' => 'GET',
        ])
        ->submit([
            'sortfield' => 't.ref',
            'ids_only' => 'true',
            'sqlfilters' => "(t.ref:in:{$refs})",
        ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $fk_products = array_map('intval', $response['data']);

    if (count($fk_products) !== count($payload['product_refs'])) {
        return new WP_Error(
            'product_search_error',
            __(
                'Inconsistencies between amount of found products and search references',
                'forms-bridge'
            ),
            [
                'response' => $response,
                'internal_refs' => $payload['product_refs'],
            ]
        );
    }

    $payload['fk_products'] = $fk_products;
    return $payload;
}
