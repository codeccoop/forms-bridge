<?php

use FORMS_BRIDGE\Addon;
use FORMS_BRIDGE\Form_Bridge_Template_Exception;

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name !== 'financoop-subscription-requests') {
            return $data;
        }

        $endpoint = implode(
            '/',
            array_slice(explode('/', $data['bridge']['endpoint']), 0, 4)
        );
        $campaign = Addon::fetch(
            'financoop',
            $data['backend'],
            $endpoint,
            null
        );

        if (empty($campaign)) {
            throw new Form_Bridge_Template_Exception(
                'financoop_api_error',
                __('Can\'t fetch campaign data', 'forms-bridge'),
                ['status' => 500]
            );
        }

        $parts_index = array_search(
            'ordered_parts',
            array_column($data['form']['fields'], 'name')
        );

        $parts_field = &$data['form']['fields'][$parts_index];

        $min = $campaign['minimal_subscription_amount'];
        if (!empty($min)) {
            $parts_field['min'] = $min;
            $parts_field['step'] = $min;
            $parts_field['default'] = $min;
        }

        $max = $campaign['maximal_subscription_amount'];
        if (!empty($max)) {
            $parts_field['max'] = $max;
        }

        return $data;
    },
    10,
    2
);

global $forms_bridge_iso2_countries;

return [
    'title' => __('Subscription Requests', 'forms-bridge'),
    'description' => __(
        'Subscription form template. The resulting bridge will convert form submissions into subscription requests.',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => '/api/campaign/{campaign_id}/subscription_request',
        ],
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Subscription Requests', 'forms-bridge'),
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/campaign/{campaign_id}/subscription_request',
        'custom_fields' => [
            [
                'name' => 'lang',
                'value' => '$locale',
            ],
            [
                'name' => 'type',
                'value' => 'increase',
            ],
        ],
        'mutations' => [
            [
                [
                    'from' => 'ordered_parts',
                    'to' => 'ordered_parts',
                    'cast' => 'integer',
                ],
            ],
            [
                [
                    'from' => 'country',
                    'to' => 'country',
                    'cast' => 'null',
                ],
            ],
        ],
        'workflow' => ['forms-bridge-iso2-country-code', 'financoop-vat-id'],
    ],
    'form' => [
        'fields' => [
            [
                'label' => __('Ordered parts', 'forms-bridge'),
                'name' => 'ordered_parts',
                'type' => 'number',
                'required' => true,
                'min' => 1,
            ],
            [
                'label' => __('First name', 'forms-bridge'),
                'name' => 'firstname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Last name', 'forms-bridge'),
                'name' => 'lastname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('ID number', 'forms-bridge'),
                'name' => 'vat',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Nationality', 'forms-bridge'),
                'name' => 'country',
                'type' => 'options',
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
                'label' => __('Email', 'forms-bridge'),
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'label' => __('Phone', 'forms-bridge'),
                'name' => 'phone',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Address', 'forms-bridge'),
                'name' => 'address',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('Zip code', 'forms-bridge'),
                'name' => 'zip_code',
                'type' => 'text',
                'required' => true,
            ],
            [
                'label' => __('City', 'forms-bridge'),
                'name' => 'city',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ],
];
