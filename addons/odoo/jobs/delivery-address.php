<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Shipping address', 'forms-bridge'),
    'description' => __(
        'Creates a shipping address linked to a contact.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_shipping_address',
    'input' => [
        [
            'name' => 'partner_id',
            'schema' => ['type' => 'integer'],
            'required' => true,
        ],
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'phone',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'mobile',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'street',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'street2',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'city',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'zip',
            'schema' => ['type' => 'string'],
        ],
        // [
        //     'name' => 'state',
        //     'schema' => ['type' => 'string'],
        // ],
        // [
        //     'name' => 'country',
        //     'schema' => ['type' => 'string'],
        // ],
        [
            'name' => 'comment',
            'schema' => ['type' => 'string'],
        ],
    ],
    'output' => [],
];

function forms_bridge_odoo_shipping_address($payload, $bridge)
{
    $query = [
        ['type', '=', 'delivery'],
        ['parent_id', '=', $payload['partner_id']],
        ['name', '=', $payload['name']],
    ];

    $response = $bridge
        ->patch([
            'name' => 'odoo-search-address',
            'method' => 'search',
            'endpoint' => 'res.partner',
        ])
        ->submit($query);

    if (!is_wp_error($response)) {
        return $payload;
    }

    $address = [];
    foreach ($query as $filter) {
        $address[$filter[0]] = $filter[2];
    }

    $address_fields = [
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
    ];

    foreach ($address_fields as $field) {
        if (isset($payload[$field])) {
            $address[$field] = $payload[$field];
        }
    }

    $response = $bridge
        ->patch([
            'name' => 'odoo-delivery-address',
            'endpoint' => 'res.partner',
            'method' => 'create',
        ])
        ->submit($address);

    if (is_wp_error($response)) {
        return $response;
    }

    return $payload;
}
