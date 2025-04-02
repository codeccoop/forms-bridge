<?php

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'forms_bridge_template_data',
    function ($data, $template_name) {
        if ($template_name !== 'financoop-subscription-request') {
            return $data;
        }

        $campaign_id = $data['bridge']['campaign_id'];
        $backend_params = $data['backend'];

        $campaign = \FORMS_BRIDGE\Finan_Coop_Addon::fetch_campaign(
            $campaign_id,
            $backend_params
        );

        if (is_wp_error($campaign)) {
            throw $campaign;
        }

        $parts_index = array_search(
            'ordered_parts',
            array_column($data['form']['fields'], 'name')
        );

        $parts_field = &$data['form']['fields'][$parts_index];

        if (!empty(($min = $campaign['minimal_subscription_amount']))) {
            $parts_field['min'] = $min;
            $parts_field['step'] = $min;
            $parts_field['default'] = $min;
        }

        if (!empty(($max = $campaign['maximal_subscription_amount']))) {
            $parts_field['max'] = $max;
        }

        return $data;
    },
    10,
    2
);

global $forms_bridge_country_codes;

return [
    'title' => __('Subscription Requests', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Subscription Requests', 'forms-bridge'),
        ],
    ],
    'bridge' => [
        'endpoint' => '/api/campaign/{campaign_id}/subscription_request',
        'mutations' => [
            [
                [
                    'from' => 'ordered_parts',
                    'to' => 'ordered_parts',
                    'cast' => 'integer',
                ],
            ],
            [],
            [],
            [
                [
                    'from' => 'locale',
                    'to' => 'lang',
                    'cast' => 'string',
                ],
            ],
        ],
        'workflow' => [
            'forms-bridge-country-code',
            'financoop-vat-id',
            'forms-bridge-current-locale',
            'financoop-campaign-id',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'source',
                'type' => 'hidden',
                'required' => true,
                'value' => 'website',
            ],
            [
                'name' => 'type',
                'type' => 'hidden',
                'required' => true,
                'value' => 'increase',
            ],
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
                    global $forms_bridge_country_codes;
                    return [
                        'value' => $country_code,
                        'label' => $forms_bridge_country_codes[$country_code],
                    ];
                }, array_keys($forms_bridge_country_codes)),
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
