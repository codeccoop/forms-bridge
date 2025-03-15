<?php

use FORMS_BRIDGE\Logger;

if (!defined('ABSPATH')) {
    exit();
}

add_filter(
    'http_request_args',
    function ($args) {
        if (
            isset($args['headers']['Origin']) &&
            $args['headers']['Origin'] === 'zoho-crm-contacts'
        ) {
            unset($args['headers']['Origin']);
        } elseif (
            isset($args['headers']['origin']) &&
            $args['headers']['origin'] === 'zoho-crm-contacts'
        ) {
            unset($args['headers']['origin']);
        }

        return $args;
    },
    10,
    1
);

add_filter(
    'forms_bridge_payload',
    function ($payload, $bridge) {
        if ($bridge->template !== 'zoho-crm-contacts') {
            return $payload;
        }

        return ['data' => [$payload]];
    },
    90,
    2
);

function forms_bridge_zoho_crm_contacts_headers()
{
    remove_filter(
        'http_bridge_backend_headers',
        'forms_bridge_zoho_crm_contacts_headers',
        10,
        0
    );

    $credentials = get_option('forms-bridge-zoho-credentials');

    try {
        $credentials = json_decode($credentials, true);
    } catch (TypeError) {
        $credentials = null;
    }

    $access_token = $credentials['access_token'] ?? '';

    return [
        'Origin' => 'zoho-crm-contacts',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => 'Zoho-oauthtoken ' . $access_token,
    ];
}

add_action(
    'forms_bridge_before_submit',
    function ($bridge) {
        if ($bridge->template !== 'zoho-crm-contacts') {
            return;
        }

        $backend = $bridge->backend;
        $headers = $backend->headers;

        add_filter(
            'http_bridge_backend_headers',
            'forms_bridge_zoho_crm_contacts_headers',
            10,
            0
        );

        $credentials = get_option('forms-bridge-zoho-credentials');

        if ($credentials) {
            try {
                $credentials = json_decode($credentials, true);
            } catch (TypeError) {
                $credentials = false;
            }
        }

        if (is_array($credentials) && isset($credentials['expires_at'])) {
            if ($credentials['expires_at'] < time() - 10) {
                // skip is access token is still valid
                return;
            }
        }

        $base_url = $backend->base_url;
        $host = parse_url($base_url)['host'] ?? null;
        if (!$host) {
            return;
        }
        $region = null;
        if (preg_match('/\.([a-z]{2,3}(\.[a-z]{2})?)$/', $host, $matches)) {
            $region = $matches[1];
        } else {
            Logger::log('Invalid Zoho API URL', Logger::ERROR);
            return;
        }

        $oauth_server = 'https://accounts.zoho.' . $region;
        $url = $oauth_server . '/oauth/v2/token';

        $query = http_build_query([
            'client_id' => $headers['client_id'] ?? '',
            'client_secret' => $headers['client_secret'] ?? '',
            'grant_type' => 'client_credentials',
            'scope' => 'ZohoCRM.modules.contacts.CREATE',
            'soid' => 'ZohoCRM.' . ($headers['organization_id'] ?? ''),
        ]);

        $response = http_bridge_post($url . '?' . $query);

        if (is_wp_error($response)) {
            Logger::log('Oauth response error', Logger::ERROR);
            Logger::log($response, Logger::ERROR);
            return;
        }

        $credentials = $response['data'];
        $credentials['expires_at'] = $response['expires_in'] + time();

        update_option(
            'forms-bridge-zoho-credentials',
            json_encode($credentials)
        );
    },
    10,
    1
);

add_filter(
    'forms_bridge_prune_empties',
    function ($prune, $bridge) {
        if ($bridge->template === 'zoho-crm-contacts') {
            return true;
        }

        return $prune;
    },
    10,
    2
);

return [
    'title' => __('Zoho CRM Contacts', 'forms-bridge'),
    'fields' => [
        [
            'ref' => '#backend',
            'name' => 'name',
            'label' => __('Backend name', 'forms-bridge'),
            'type' => 'string',
            'default' => 'Zoho CRM API',
        ],
        [
            'ref' => '#backend',
            'name' => 'base_url',
            'label' => __('Zoho API server URL', 'forms-bridge'),
            'type' => 'string',
            'default' => 'https://www.zohoapis.com',
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
            'value' => '/crm/v7/Contacts',
        ],
        [
            'ref' => '#bridge',
            'name' => 'method',
            'label' => __('Method', 'forms-bridge'),
            'type' => 'string',
            'value' => 'POST',
        ],
    ],
    'form' => [
        'fields' => [
            [
                'name' => 'First_Name',
                'label' => __('First name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Last_Name',
                'label' => __('Last name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'Email',
                'label' => __('Email', 'forms-bridge'),
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'Phone',
                'label' => __('Phone', 'forms-bridge'),
                'type' => 'text',
            ],
            [
                'name' => 'Mobile',
                'label' => __('Mobile', 'forms-bridge'),
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
        'endpoint' => '/crm/v7/Contacts',
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
