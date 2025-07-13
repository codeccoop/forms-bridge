<?php

use FORMS_BRIDGE\Addon;

if (!defined('ABSPATH')) {
    exit();
}

$setting_names = [
    'bigin',
    'brevo',
    'dolibarr',
    'financoop',
    'gsheets',
    'holded',
    'listmonk',
    'mailchimp',
    'odoo',
    'rest-api',
    'zoho',
];

foreach ($setting_names as $setting_name) {
    $option = 'forms-bridge_' . $setting_name;

    $data = get_option($option, []);

    $addon = Addon::addon($setting_name);
    $data['title'] = $addon::title;

    if (!isset($data['bridges'])) {
        $data['bridges'] = [];
    }

    foreach ($data['bridges'] as &$bridge_data) {
        if (!isset($bridge_data['workflow'])) {
            continue;
        }

        $i = 0;
        while ($i < count($bridge_data['workflow'])) {
            $job_name = $bridge_data['workflow'][$i];

            if (strpos('forms-bridge-', $job_name) === 0) {
                $job_name = substr($job_name, 13);
            } elseif (strpos($job_name, $setting_name) === 0) {
                $job_name = substr($job_name, strlen($setting_name) + 1);
            }

            $bridge_data['workflow'][$i] = $job_name;
            $i++;
        }

        $bridge_class = $addon::bridge_class;
        $bridge_data = wpct_plugin_sanitize_with_schema(
            $bridge_data,
            $bridge_class::schema()
        );
    }

    if (
        !in_array($option, [
            'forms-bridge_odoo',
            'forms-bridge_zoho',
            'forms-bridge_bigin',
            'forms-bridge_gsheets',
            true,
        ])
    ) {
        unset($data['credentials']);
    }

    update_option($option, $data);
}

$http = get_option('http-bridge_general', []);

if (isset($http['backends'])) {
    foreach ($http['backends'] as &$backend) {
        $header_names = array_column($backend['headers'], 'name');
        $user_index = array_search('api_user', $header_names);
        $token_index = array_search('token', $header_names);

        if ($user_index !== false && $token_index !== false) {
            $user = $backend['headers'][$user_index]['value'];
            $token = $backend['headers'][$user_index]['value'];

            $headers = [];
            foreach ($backend['headers'] as $header) {
                if (!in_array($header['name'], ['api_user', 'token'], true)) {
                    $headers[] = $header;
                }
            }

            $headers[] = [
                'name' => 'Authorization',
                'value' => "token {$user}:{$token}",
            ];

            $backend['headers'] = $headers;
        } elseif (strstr($backend['base_url'], 'api.mailchimp.com')) {
            $index = array_search(
                'api-key',
                array_column($backend['headers'], 'name')
            );

            if ($index !== false) {
                $key = $backend['headers'][$index]['value'];
                $backend['headers'][] = [
                    'name' => 'Authorization',
                    'value' => 'Basic ' . base64_encode("forms-bridge:{$key}"),
                ];

                array_splice($backend['headers'], $index, 1);
            }
        }
    }

    update_option('http-bridge_general', $http);
}

$rest = get_option('forms-bridge_rest-api');
add_option('forms-bridge_rest', $rest);
delete_option('forms-bridge_rest-api');

$registry = get_option('forms_bridge_addons');
$registry['rest'] = $registry['rest-api'];
unset($registry['rest-api']);
update_option('forms_bridge_addons', $registry);

// TODO: Add type and token attributes to zoho credentials
