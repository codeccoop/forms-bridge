<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_zoho_bigin_account_name($payload, $bridge)
{
    $company = [
        'Account_Name' => $payload['Account_Name'],
    ];

    $company_fields = [
        'Billing_Street',
        'Billing_Code',
        'Billing_City',
        'Billing_State',
        'Billing_Country',
        'Description',
    ];

    foreach ($company_fields as $field) {
        if (isset($payload[$field])) {
            $company[$field] = $payload[$field];
        }
    }

    $response = $bridge
        ->patch([
            'name' => 'zoho-bigin-account-name',
            'endpoint' => '/bigin/v2/Accounts',
            'template' => null,
        ])
        ->submit($company);

    if (is_wp_error($response)) {
        $data = json_decode(
            $response->get_error_data()['response']['body'],
            true
        );

        if ($data['data'][0]['code'] !== 'DUPLICATE_DATA') {
            return $response;
        }

        $account_id = $data['data'][0]['details']['duplicate_record']['id'];
    } else {
        $account_id = $response['data']['data'][0]['details']['id'];
    }

    $payload['Account_Name'] = [
        'id' => $account_id,
    ];

    return $payload;
}

return [
    'title' => __('Bigin account name', 'forms-bridge'),
    'description' => __(
        'Search for an account by name or creates a new if it does\'t exists and replace the name by the ID on the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_zoho_bigin_account_name',
    'input' => [
        [
            'name' => 'Account_Name',
            'type' => 'string',
            'required' => true,
        ],
        [
            'name' => 'Billing_Street',
            'type' => 'string',
        ],
        [
            'name' => 'Billing_Code',
            'type' => 'string',
        ],
        [
            'name' => 'Billing_City',
            'type' => 'string',
        ],
        [
            'name' => 'Billing_State',
            'type' => 'string',
        ],
        [
            'name' => 'Billing_Country',
            'type' => 'string',
        ],
        [
            'name' => 'Description',
            'type' => 'string',
        ],
    ],
    'output' => [
        [
            'name' => 'Account_Name',
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string'],
            ],
        ],
    ],
];
