<?php

use FORMS_BRIDGE\Addon;

if (!defined('ABSPATH')) {
    exit();
}

$rest = get_option('forms-bridge_rest-api');
add_option('forms-bridge_rest', $rest);
delete_option('forms-bridge_rest-api');

$registry = get_option('forms_bridge_addons');
$registry['rest'] = $registry['rest-api'];
unset($registry['rest-api']);
update_option('forms_bridge_addons', $registry);

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
    'rest',
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

    if (!isset($data['credentials'])) {
        $data['credentials'] = [];
    }

    $backends = [];
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

        if ($backend = $bridge_data['backend'] ?? null) {
            if (!in_array($backend, $backends, true)) {
                $backends[] = $backend;
            }
        }
    }

    if ($option === 'forms-bridge_listmonk') {
        foreach ($backends as $name) {
            $backend = FBAPI::get_backend($name);
            if (!$backend) {
                continue;
            }

            $headers = $backend->headers;
            if (isset($headers['api_user'], $headers['token'])) {
                $data = [
                    'name' => $backend->name,
                    'base_url' => $backend->base_url,
                    'headers' => [],
                ];

                $credential = [
                    'name' => $backend->name,
                    'schema' => 'Token',
                    'client_id' => $headers['api_user'],
                    'client_secret' => $headers['token'],
                ];

                unset($headers['api_user']);
                unset($headers['token']);

                foreach ($headers as $name => $value) {
                    $data['headers'][] = ['name' => $name, 'value' => $value];
                }

                FBAPI::save_backend($data);
                $data['credentials'][] = $credential;
            }
        }
    } elseif ($option === 'forms-bridge_mailchimp') {
        foreach ($backends as $name) {
            $backend = FBAPI::get_backend($name);
            if (!$backend) {
                continue;
            }

            $headers = $backend->headers;
            if (isset($headers['api-key'])) {
                $data = [
                    'name' => $backend->name,
                    'base_url' => $backend->base_url,
                    'headers' => [],
                ];

                $credential = [
                    'name' => $backend->name,
                    'schema' => 'Basic',
                    'client_id' => 'forms-bridge',
                    'client_secret' => $headers['api-key'],
                ];

                unset($headers['api-key']);

                foreach ($headers as $name => $value) {
                    $data['headers'][] = ['name' => $name, 'value' => $value];
                }

                FBAPI::save_backend($data);
                $data['credentials'][] = $credential;
            }
        }
    } elseif ($option = 'forms-bridge_financoop') {
        foreach ($backends as $name) {
            $backend = FBAPI::get_backend($name);
            if (!$backend) {
                continue;
            }

            $headers = $backend->headers;
            if (
                isset(
                    $headers['X-Odoo-Db'],
                    $headers['X-Odoo-Username'],
                    $headers['X-Odoo-Api-Key']
                )
            ) {
                $data = [
                    'name' => $backend->name,
                    'base_url' => $backend->base_url,
                    'headers' => [],
                ];

                $credential = [
                    'name' => $backend->name,
                    'schema' => 'RPC',
                    'client_id' => $headers['X-Odoo-Username'],
                    'client_secret' => $headers['X-Odoo-Api-Key'],
                    'realm' => $headers['X-Odoo-Db'],
                ];

                unset($headers['X-Odoo-Db']);
                unset($headers['X-Odoo-Username']);
                unset($headers['X-Odoo-Api-Key']);

                foreach ($headers as $name => $value) {
                    $data['headers'][] = ['name' => $name, 'value' => $value];
                }

                FBAPI::save_backend($data);
                $data['credentials'][] = $credential;
            }
        }
    } elseif ($option === 'forms-bridge_odoo') {
        $credentials = [];
        foreach ($data['credentials'] as $credential) {
            $credentials[] = [
                'name' => $credential['name'],
                'schema' => 'RPC',
                'client_id' => $credential['user'],
                'client_secret' => $credential['password'],
                'realm' => $credential['database'],
            ];
        }

        $data['credentials'] = $credentials;
    } elseif ($option === 'forms-bridge_zoho') {
        $credentials = [];
        foreach ($data['credentials'] as $credential) {
            $credential['schema'] = 'OAuth';
            $credential['type'] = 'Self Client';
            $credential['realm'] =
                'ZohoCRM.modules.ALL,ZohoCRM.settings.layouts.READ,ZohoCRM.users.READ';
            $credentials[] = $credential;
        }

        $data['credentials'] = $credential;
    } elseif ($option === 'forms-bridge_bigin') {
        $credentials = [];
        foreach ($data['credentials'] as $credential) {
            $credential['schema'] = 'OAuth';
            $credential['type'] = 'Self Client';
            $credential['realm'] =
                'BiginCRM.modules.ALL,BiginCRM.settings.layouts.READ,BiginCRM.users.READ';
            $credentials[] = $credential;
        }

        $data['credentials'] = $credential;
    }

    update_option($option, $data);
}
