<?php

use FORMS_BRIDGE\Zoho_Form_Bridge;

if (!defined('ABSPATH')) {
    exit();
}

$forms_bridge_zoho_company_contact_data = null;
$forms_bridge_zoho_company_request = null;

add_filter(
    'forms_bridge_prune_empties',
    function ($prune, $bridge) {
        if ($bridge->template === 'zoho-bigin-companies') {
            return true;
        }

        return $prune;
    },
    10,
    2
);

add_action(
    'forms_bridge_before_submit',
    function ($bridge) {
        if ($bridge->template === 'zoho-bigin-companies') {
            add_action(
                'http_bridge_response',
                'forms_bridge_on_bigin_company_http_response',
                10,
                2
            );
        }
    },
    10,
    1
);

function forms_bridge_on_bigin_company_http_response($res, $req)
{
    if (strstr($req['url'], '/bigin/v2/Accounts') !== false) {
        remove_action(
            'http_bridge_response',
            'forms_bridge_on_company_http_response',
            10,
            2
        );

        global $forms_bridge_zoho_company_request;
        $forms_bridge_zoho_company_request = $req;
    }
}

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template === 'zoho-bigin-companies') {
            global $forms_bridge_zoho_company_contact_data;
            $forms_bridge_zoho_company_contact_data = [];

            foreach ($payload as $field => $value) {
                if (strpos($field, 'Contact_') === 0) {
                    $contact_field = str_replace('Contact_', '', $field);
                    $forms_bridge_zoho_company_contact_data[
                        $contact_field
                    ] = $value;
                    unset($payload[$field]);
                }
            }
        }

        return $payload;
    },
    9,
    2
);

add_action(
    'forms_bridge_submit',
    function ($bridge, $response) {
        if ($bridge->template !== 'zoho-bigin-companies') {
            return;
        }

        global $forms_bridge_zoho_company_request;
        if (
            empty($forms_bridge_zoho_company_request) ||
            is_wp_error($forms_bridge_zoho_company_request)
        ) {
            return;
        }

        $authorization =
            $forms_bridge_zoho_company_request['args']['headers'][
                'Authorization'
            ] ?? '';

        global $forms_bridge_zoho_company_contact_data;
        if (empty($forms_bridge_zoho_company_contact_data)) {
            return;
        }

        $account_id = $response['data']['data'][0]['details']['id'];

        $payload = $forms_bridge_zoho_company_contact_data;
        $payload['Account_Name'] = [
            'id' => $account_id,
        ];

        $backend = $bridge->backend;
        $response = $backend->post(
            '/bigin/v2/Contacts',
            ['data' => [$payload]],
            [
                'Origin' => Zoho_Form_Bridge::http_origin_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $authorization,
            ]
        );

        if (is_wp_error($response)) {
            do_action('forms_bridge_on_failure', $bridge, $response, $payload);
        }
    },
    10,
    2
);

return [
    'title' => __('Zoho Bigin Companies', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#backend',
            'name' => 'name',
            'label' => __('Backend name', 'forms-bridge'),
            'type' => 'string',
            'default' => 'Zoho Bigin API',
        ],
        [
            'ref' => '#backend/headers[]',
            'name' => 'organization_id',
            'label' => __('Organization ID', 'form-bridge'),
            'description' => __(
                'From your organization dashboard, expand the profile sidebar and click on the copy user ID icon to get your organization ID.',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#backend/headers[]',
            'name' => 'client_id',
            'label' => __('Client ID', 'forms-bridge'),
            'description' => __(
                'You have to create a Self-Client Application on the Zoho Developer Console and get the Client ID',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#backend/headers[]',
            'name' => 'client_secret',
            'label' => __('Client Secret', 'forms-bridge'),
            'description' => __(
                'You have to create a Self-Client Application on the Zoho Developer Console and get the Client Secret',
                'forms-bridge'
            ),
            'type' => 'string',
            'required' => true,
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'label' => __('Endpoint', 'forms-bridge'),
            'type' => 'string',
            'value' => '/bigin/v2/Accounts',
        ],
        [
            'ref' => '#bridge',
            'name' => 'scope',
            'label' => __('Scope', 'forms-bridge'),
            'type' => 'string',
            'value' =>
                'ZohoBigin.modules.accounts.CREATE,ZohoBigin.modules.contacts.CREATE',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'Account_Name',
                'label' => __('Company name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Phone',
                'label' => __('Phone', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Website',
                'label' => __('Website', 'forms-bridge'),
                'type' => 'url',
            ],
            [
                'name' => 'Billing_Street',
                'label' => __('Street', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_City',
                'label' => __('City', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_State',
                'label' => __('State', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_Country',
                'label' => __('Country', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Billing_Code',
                'label' => __('Postal code', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Contact_First_Name',
                'label' => __('First name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Contact_Last_Name',
                'label' => __('Last name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Contact_Title',
                'label' => __('Title', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Contact_Email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Description',
                'label' => __('Comments', 'forms-bridge'),
                'type' => 'textarea',
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => '/bigin/v2/Accounts',
        'scope' =>
            'ZohoBigin.modules.accounts.CREATE,ZohoBigin.modules.contacts.CREATE',
    ],
    'backend' => [
        'base_url' => 'https://www.zohoapis.com',
        'headers' => [
            [
                'name' => 'Accept',
                'value' => 'application/json',
            ],
        ],
    ],
];
