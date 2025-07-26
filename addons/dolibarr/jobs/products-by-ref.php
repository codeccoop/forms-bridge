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
    $sqlfilters = [];
    $refs = (array) $payload['product_refs'];
    foreach ($refs as $ref) {
        $ref = trim($ref);
        $sqlfilters[] = "(t.ref:=:'{$ref}')";
    }

    $response = $bridge
        ->patch([
            'name' => 'dolibarr-search-products-by-ref',
            'endpoint' => '/api/index.php/products',
            'method' => 'GET',
        ])
        ->submit([
            'properties' => 'id,ref',
            'sqlfilters' => implode(' or ', $sqlfilters),
        ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $fk_products = [];
    foreach ($refs as $ref) {
        foreach ($response['data'] as $product) {
            if ($product['ref'] === $ref) {
                $fk_products[] = $product['id'];
                break;
            }
        }
    }

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
