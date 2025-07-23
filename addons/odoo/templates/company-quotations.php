<?php

if (!defined('ABSPATH')) {
    exit();
}

global $forms_bridge_iso2_countries;

return [
    'title' => __('Company Quotations', 'forms-bridge'),
    'description' => __(
        'Quotations form template. The resulting bridge will convert form submissions into quotations linked to new companies.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Company Quotations', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'sale.order',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'product_id',
            'label' => __('Product', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                'endpoint' => 'product.product',
                'finger' => [
                    'value' => 'result[].id',
                    'label' => 'result[].name',
                ],
            ],
            'required' => true,
        ],
    ],
    'bridge' => [
        'endpoint' => 'sale.order',
        'custom_fields' => [
            [
                'name' => 'state',
                'value' => 'draft',
            ],
            [
                'name' => 'order_line[0][0]',
                'value' => '0',
            ],
            [
                'name' => 'order_line[0][1]',
                'value' => '0',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'order_line[0][0]',
                    'to' => 'order_line[0][0]',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'order_line[0][1]',
                    'to' => 'order_line[0][1]',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'quantity',
                    'to' => 'order_line[0][2].product_uom_qty',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'product_id',
                    'to' => 'order_line[0][2].product_id',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'company-name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
            ],
            [],
            [],
            [
                [
                    'from' => 'email',
                    'to' => 'contact_email',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'phone',
                    'to' => 'contact_phone',
                    'cast' => 'copy',
                ],
            ],
            [
                [
                    'from' => 'your-name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'parent_id',
                    'to' => 'company_partner_id',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'contact_email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
                [
                    'from' => 'contact_phone',
                    'to' => 'phone',
                    'cast' => 'string',
                ],
            ],
            [
                [
                    'from' => 'company_partner_id',
                    'to' => 'partner_id',
                    'cast' => 'integer',
                ],
            ],
        ],
        'workflow' => [
            'iso2-country-code',
            'vat-id',
            'country-id',
            'contact-company',
            'contact',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Quantity', 'forms-bridge'),
                'name' => 'quantity',
                'type' => 'number',
                'required' => true,
                'default' => 1,
                'min' => 1,
            ],
            [
                'label' => __('Company', 'forms-bridge'),
                'name' => 'company-name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Vat ID', 'forms-bridge'),
                'name' => 'vat',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'street',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Country', 'forms-bridge'),
                'name' => 'country',
                'type' => 'select',
                'options' => array_map(function ($country_code) {
                    global $forms_bridge_iso2_countries;
                    return [
                        'value' => $country_code,
                        'label' => $forms_bridge_iso2_countries[$country_code],
                    ];
                }, array_keys($forms_bridge_iso2_countries)),
                'required' => true,
            ],
            [
                'label' => __('Your name', 'forms-bridge'),
                'name' => 'your-name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Your email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Your phone', 'forms-bridge'),
                'name' => 'phone',
                'type' => 'text',
            ],
        ],
    ],
];
