<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Contact account', 'forms-bridge'),
    'description' => __(
        'Create an account and sets its id as the Account_Name field on the payload',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_zoho_crm_contact_account',
    'input' => [
        [
            'name' => 'Account_Name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'Rating',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'Billing_Street',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Billing_City',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Billing_Code',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Billing_State',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Billing_Country',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Phone',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Fax',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Mobile',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Website',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Owner',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                ],
                'required' => ['id'],
                'additionalProperties' => false,
            ],
        ],
        [
            'name' => 'Industry',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Ownership',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Employees',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'Description',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'Tag',
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                    'additionalProperties' => false,
                    'required' => ['name'],
                ],
            ],
        ],
    ],
    'output' => [
        [
            'name' => 'Account_Name',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string'],
                ],
                'additionalProperties' => false,
            ],
        ],
    ],
];

function forms_bridge_zoho_crm_contact_account($payload, $bridge)
{
    $account = forms_bridge_zoho_crm_create_account($payload, $bridge);

    if (is_wp_error($account)) {
        return $account;
    }

    $payload['Account_Name'] = ['id' => $account['id']];
    return $payload;
}
